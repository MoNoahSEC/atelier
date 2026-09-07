<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Portal Login — Atelier Studio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f5f5f0; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-8">
    <div class="w-full max-w-md">
        
        <!-- Header Branding -->
        <div class="text-center mb-8">
            <div class="inline-block border-2 border-black bg-black text-white px-3 py-1 text-[10px] font-bold uppercase tracking-[0.25em] mb-3">
                RESTRICTED SYSTEM ACCESS
            </div>
            <h1 class="text-3xl font-black uppercase tracking-tight text-black">ATELIER CONSOLE</h1>
            <p class="text-xs text-gray-600 mt-1 uppercase tracking-wider font-bold">Admin Authentication Portal</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white border-2 border-black p-8 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
            
            <!-- Flash & Error Notices -->
            @if(session('success'))
                <div class="bg-black text-white p-3 mb-6 text-xs font-bold uppercase tracking-wider border-l-4 border-green-500">
                    ✓ {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 text-red-900 border-2 border-red-600 p-3.5 mb-6 text-xs font-bold">
                    ⚠️ {{ $errors->first('login') ?: $errors->first() }}
                </div>
            @endif

            <!-- Form -->
            <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-[10px] font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                        Administrator Email
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        required 
                        autofocus
                        value="{{ old('email') }}"
                        placeholder="admin@atelier.com" 
                        class="w-full border-2 border-black p-3 text-sm font-medium focus:outline-none focus:bg-gray-50 transition-colors"
                    >
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-[10px] font-bold uppercase tracking-wider text-gray-700">
                            Password
                        </label>
                    </div>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        required 
                        placeholder="••••••••••••" 
                        class="w-full border-2 border-black p-3 text-sm font-medium focus:outline-none focus:bg-gray-50 transition-colors"
                    >
                </div>

                <div class="pt-2">
                    <button 
                        type="submit" 
                        class="w-full bg-black text-white py-3.5 px-6 text-xs font-bold uppercase tracking-[0.2em] hover:bg-gray-900 active:translate-y-0.5 shadow-[4px_4px_0px_0px_rgba(0,0,0,0.3)] transition-all flex items-center justify-center gap-2"
                    >
                        <span>Authenticate</span>
                        <span>→</span>
                    </button>
                </div>
            </form>

            <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                <a href="{{ route('home') }}" class="text-[11px] font-bold uppercase tracking-wider text-gray-500 hover:text-black transition-colors">
                    ← Return to Storefront
                </a>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-[10px] uppercase tracking-widest text-gray-500 mt-6 font-mono">
            Encrypted Session · Atelier Security Architecture
        </p>

    </div>
</body>
</html>
