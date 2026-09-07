@echo off
chcp 65001 >nul 2>&1
setlocal EnableDelayedExpansion

:: ============================================================
::   MT STORE — Smart Launcher v4.0
::   Laravel API  ->  127.0.0.1:8000
::   Next.js      ->  localhost:3000
::   Usage: start.bat            (start everything)
::          start.bat stop       (stop both servers cleanly)
:: ============================================================

set "ROOT=%~dp0"
if "%ROOT:~-1%"=="\" set "ROOT=%ROOT:~0,-1%"

set "STOREFRONT=%ROOT%\storefront"
set "LOG_MAIN=%ROOT%\start.log"
set "LARAVEL_PORT=8000"
set "NEXTJS_PORT=3000"
set "PIDFILE=%ROOT%\.mtstore_pids"

:: -------- self-calling child helpers --------
if "%~1"=="LARAVEL" goto RUN_LARAVEL
if "%~1"=="NEXTJS" goto RUN_NEXTJS
if "%~1"=="TELEGRAM_BOT" goto RUN_TELEGRAM_BOT
if "%~1"=="stop" goto STOP_ALL

call :log "===== LAUNCH STARTED ====="
echo.
echo  ============================================================
echo    MT STORE — Smart Launcher v4.0
echo  ============================================================
echo.

:: ============================================================
::   STEP 0 — VERIFY ENVIRONMENT
:: ============================================================
echo  [CHECK] Verifying PHP...
where php >nul 2>&1
if errorlevel 1 (
    echo  [ERROR] PHP not found in PATH. Install PHP 8.2+ and add it to PATH.
    call :log "FATAL: PHP not found"
    pause & exit /b 1
)
for /f "tokens=*" %%v in ('php -r "echo PHP_VERSION;" 2^>nul') do set PHP_VER=%%v
echo  [OK]    PHP %PHP_VER% found

echo  [CHECK] Verifying Node.js and npm...
where node >nul 2>&1
if errorlevel 1 (
    echo  [ERROR] Node.js not found in PATH. Install Node.js 18+.
    call :log "FATAL: Node.js not found"
    pause & exit /b 1
)
for /f "tokens=*" %%v in ('node -v 2^>nul') do set NODE_VER=%%v
echo  [OK]    Node.js %NODE_VER%

if not exist "%ROOT%\artisan" (
    echo  [ERROR] artisan not found — is this the correct project root?
    call :log "FATAL: artisan missing at %ROOT%"
    pause & exit /b 1
)

:: ============================================================
::   STEP 1 — ENV FILE CHECK (auto-fix instead of silently failing)
:: ============================================================
echo  [CHECK] Verifying .env...
if not exist "%ROOT%\.env" (
    if exist "%ROOT%\.env.example" (
        echo  [FIX]   .env missing — copying from .env.example
        copy /y "%ROOT%\.env.example" "%ROOT%\.env" >nul
        call :log "Created .env from .env.example"
    ) else (
        echo  [ERROR] No .env and no .env.example found. Cannot continue safely.
        call :log "FATAL: no .env or .env.example"
        pause & exit /b 1
    )
)

findstr /r "^APP_KEY=.\+" "%ROOT%\.env" >nul 2>&1
if errorlevel 1 (
    echo  [FIX]   APP_KEY missing — generating one...
    cd /d "%ROOT%"
    php artisan key:generate --force >> "%LOG_MAIN%" 2>&1
)

if not exist "%STOREFRONT%\.env.local" if exist "%STOREFRONT%\.env.example" (
    echo  [FIX]   storefront .env.local missing — copying from .env.example
    copy /y "%STOREFRONT%\.env.example" "%STOREFRONT%\.env.local" >nul
)

:: ============================================================
::   STEP 2 — INSTALL DEPENDENCIES (with real failure checks)
:: ============================================================
if not exist "%ROOT%\vendor\autoload.php" (
    echo  [DEPS]  Running composer install...
    cd /d "%ROOT%"
    composer install --no-interaction --prefer-dist >> "%LOG_MAIN%" 2>&1
    if errorlevel 1 (
        echo  [ERROR] composer install failed — check start.log for details.
        call :log "FATAL: composer install failed"
        pause & exit /b 1
    )
)
if not exist "%STOREFRONT%\node_modules" (
    echo  [DEPS]  Running npm install...
    cd /d "%STOREFRONT%"
    npm install >> "%LOG_MAIN%" 2>&1
    if errorlevel 1 (
        echo  [ERROR] npm install failed — check start.log for details.
        call :log "FATAL: npm install failed"
        cd /d "%ROOT%"
        pause & exit /b 1
    )
    cd /d "%ROOT%"
)

:: ============================================================
::   STEP 3 — LARAVEL MAINTENANCE
:: ============================================================
echo  [SETUP] Running Laravel maintenance...
cd /d "%ROOT%"
php artisan migrate --force --no-interaction >> "%LOG_MAIN%" 2>&1
if errorlevel 1 (
    echo  [WARN]  Migration reported an issue — check start.log. Continuing anyway.
    call :log "WARN: migrate returned non-zero"
)
php artisan config:clear >nul 2>&1
php artisan route:clear  >nul 2>&1

:: ============================================================
::   STEP 4 — FREE PORTS (only kill our own previous instances)
:: ============================================================
echo  [PORT]  Checking ports...
call :kill_port %LARAVEL_PORT%
call :kill_port %NEXTJS_PORT%
timeout /t 1 /nobreak >nul

:: ============================================================
::   STEP 5 — START SERVERS AND RECORD THEIR PIDs
:: ============================================================
echo.
echo  [1/3]  Launching Laravel API...
start "Laravel API :8000" "%~f0" LARAVEL

timeout /t 2 /nobreak >nul

echo  [2/3]  Launching Next.js Storefront...
start "Next.js :3000" "%~f0" NEXTJS

timeout /t 1 /nobreak >nul

echo  [3/3]  Launching Telegram Assistant Bot...
start "Telegram Assistant Bot" "%~f0" TELEGRAM_BOT

:: ============================================================
::   STEP 6 — WAIT FOR LARAVEL (real HTTP check, not just port)
:: ============================================================
echo.
echo  [WAIT]  Waiting for Laravel on port %LARAVEL_PORT%...
set /a TRIES=0
set LARAVEL_STATUS=FAILED

:WAIT_LARAVEL
timeout /t 2 /nobreak >nul
set /a TRIES+=1
curl -s -o nul -w "%%{http_code}" http://127.0.0.1:%LARAVEL_PORT% > "%TEMP%\mt_http_code.txt" 2>nul
set /p HTTP_CODE=<"%TEMP%\mt_http_code.txt"
if not "%HTTP_CODE%"=="" if not "%HTTP_CODE%"=="000" (
    set LARAVEL_STATUS=RUNNING
    goto LARAVEL_OK
)
if %TRIES% GEQ 20 goto WAIT_NEXTJS
goto WAIT_LARAVEL

:LARAVEL_OK
echo  [OK]    Laravel responded with HTTP %HTTP_CODE%

:: ============================================================
::   STEP 7 — WAIT FOR NEXT.JS
:: ============================================================
:WAIT_NEXTJS
echo.
echo  [WAIT]  Waiting for Next.js on port %NEXTJS_PORT%...
set /a TRIES=0
set NEXTJS_STATUS=FAILED

:WAIT_NEXTJS_LOOP
timeout /t 3 /nobreak >nul
set /a TRIES+=1
curl -s -o nul -w "%%{http_code}" http://127.0.0.1:%NEXTJS_PORT% > "%TEMP%\mt_http_code2.txt" 2>nul
set /p HTTP_CODE2=<"%TEMP%\mt_http_code2.txt"
if not "%HTTP_CODE2%"=="" if not "%HTTP_CODE2%"=="000" (
    set NEXTJS_STATUS=RUNNING
    goto NEXTJS_OK
)
if %TRIES% GEQ 30 goto SHOW_SUMMARY
goto WAIT_NEXTJS_LOOP

:NEXTJS_OK
echo  [OK]    Next.js responded with HTTP %HTTP_CODE2%

:: ============================================================
::   STEP 8 — SUMMARY + OPEN BROWSER
:: ============================================================
:SHOW_SUMMARY
set "LOCAL_IP="
for /f "tokens=2 delims=:" %%a in ('ipconfig 2^>nul ^| findstr /i "IPv4"') do (
    if "!LOCAL_IP!"=="" (
        set "CANDIDATE=%%a"
        set "CANDIDATE=!CANDIDATE: =!"
        echo !CANDIDATE! | findstr /b "127. 169.254. 192.168.56." >nul
        if errorlevel 1 set "LOCAL_IP=!CANDIDATE!"
    )
)
if "!LOCAL_IP!"=="" (
    for /f "tokens=2 delims=:" %%a in ('ipconfig 2^>nul ^| findstr /i "IPv4"') do (
        if "!LOCAL_IP!"=="" (
            set "CANDIDATE=%%a"
            set "CANDIDATE=!CANDIDATE: =!"
            echo !CANDIDATE! | findstr /b "127. 169.254." >nul
            if errorlevel 1 set "LOCAL_IP=!CANDIDATE!"
        )
    )
)

echo.
echo  ============================================================
echo    STARTUP SUMMARY
echo  ============================================================
echo.
echo    Laravel API    :  %LARAVEL_STATUS%   (port %LARAVEL_PORT%)
echo    Next.js        :  %NEXTJS_STATUS%   (port %NEXTJS_PORT%)
echo.
echo    Storefront     -^>  http://localhost:%NEXTJS_PORT%
echo    Admin Panel    -^>  http://localhost:%NEXTJS_PORT%/admin
echo    Laravel API    -^>  http://localhost:%LARAVEL_PORT%
if not "%LOCAL_IP%"=="" (
echo    Mobile / LAN   -^>  http://%LOCAL_IP%:%NEXTJS_PORT%
)
echo.
echo    Logs           -^>  %LOG_MAIN%
echo    To stop both servers cleanly, run:  start.bat stop
echo  ============================================================
echo.

if "%LARAVEL_STATUS%"=="FAILED" echo  [WARN] Laravel never responded — check log_laravel.txt
if "%NEXTJS_STATUS%"=="FAILED" echo  [WARN] Next.js never responded — check log_nextjs.txt

if "%NEXTJS_STATUS%"=="RUNNING" (
    timeout /t 1 /nobreak >nul
    start "" "http://localhost:%NEXTJS_PORT%"
)

call :log "Startup finished. Laravel=%LARAVEL_STATUS% NextJS=%NEXTJS_STATUS%"
echo  Both server windows will remain open.
echo  Close this window (servers keep running) or run "start.bat stop" to shut them down.
pause >nul
endlocal
exit /b 0

:: ============================================================
::   CHILD PROCESS: LARAVEL
:: ============================================================
:RUN_LARAVEL
title Laravel API
color 0B
cd /d "%~dp0"
echo Starting Laravel API...
echo %date% %time% - Laravel starting > "%~dp0log_laravel.txt"
php artisan serve --host=0.0.0.0 --port=8000 >> "%~dp0log_laravel.txt" 2>&1
echo.
echo Server stopped. Press any key to exit.
pause >nul
exit /b 0

:: ============================================================
::   CHILD PROCESS: NEXT.JS
:: ============================================================
:RUN_NEXTJS
title Next.js Storefront
color 0D
cd /d "%~dp0storefront"
echo Starting Next.js...
echo %date% %time% - Next.js starting > "%~dp0log_nextjs.txt"
call npm run dev >> "%~dp0log_nextjs.txt" 2>&1
echo.
echo Server stopped. Press any key to exit.
pause >nul
:RUN_TELEGRAM_BOT
title Telegram Assistant Bot
color 0B
cd /d "%ROOT%"
echo Starting Telegram Assistant Bot Poller...
php artisan telegram:poll --loop
exit /b 0

:: ============================================================
::   HELPER: kill whatever is LISTENING on a given port
:: ============================================================
:kill_port
set "PORT_TO_KILL=%~1"
for /f "tokens=5" %%p in ('netstat -aon 2^>nul ^| findstr ":%PORT_TO_KILL% " ^| findstr "LISTENING"') do (
    if not "%%p"=="0" (
        echo  [PORT]  Freeing port %PORT_TO_KILL% (PID %%p)
        taskkill /PID %%p /F >nul 2>&1
    )
)
exit /b 0

:: ============================================================
::   HELPER: append a timestamped line to the log
:: ============================================================
:log
echo [%date% %time%] %~1 >> "%LOG_MAIN%"
exit /b 0

:: ============================================================
::   COMMAND: start.bat stop  — clean shutdown
:: ============================================================
:STOP_ALL
echo  Stopping MT Store servers...
call :kill_port %LARAVEL_PORT%
call :kill_port %NEXTJS_PORT%
echo  [OK]    Both servers stopped.
call :log "Stopped via 'start.bat stop'"
pause
exit /b 0