<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(append: [
            \App\Http\Middleware\PreventBrowserCaching::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\PreventBrowserCaching::class,
            \App\Http\Middleware\CheckMaintenanceMode::class,
            \App\Http\Middleware\SetStoreLocale::class,
            \App\Http\Middleware\TrackPresence::class,
        ]);
        $middleware->append(\App\Http\Middleware\CorrelationId::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->alias([
            'hmac'        => \App\Http\Middleware\CheckAutomationHmac::class,
            'abilities'   => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability'     => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'admin.auto'  => \App\Http\Middleware\AdminAutoAuth::class,
            'admin'       => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
        
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->expectsJson() || $request->is('api/*');
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
        });

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                // Ensure proper structure per spec
                $message = app()->isProduction() && $response->getStatusCode() >= 500 
                            ? 'Server Error' 
                            : $exception->getMessage();
                
                // If it's a validation exception, include errors in data
                $data = null;
                if ($exception instanceof \Illuminate\Validation\ValidationException) {
                    $data = ['errors' => $exception->errors()];
                }

                return response()->json([
                    'success'    => false,
                    'message'    => $message,
                    'error_code' => $exception->getCode(),
                    'request_id' => $request->header('X-Correlation-ID', ''),
                    'data'       => $data,
                ], $response->getStatusCode());
            }
            return $response;
        });
    })->create();
