@extends('layouts.app')

@section('title', 'Set New Password — ' . ($settings['storeName'] ?? 'ATELIER'))

@section('content')
<div class="bg-[#F5F5F0] min-h-screen py-16 lg:py-24">
    <div class="max-w-md mx-auto px-4 sm:px-6">

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 p-4 text-xs mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="border-2 border-black bg-white p-8 sm:p-10 shadow-[8px_8px_0px_0px_rgba(0,0,0,1)]">
            <div class="border-b border-black pb-4 mb-6">
                <span class="font-editorial font-bold text-[10px] uppercase tracking-[0.25em] text-black/60 block mb-1">
                    CONFIDENTIAL ACCESS
                </span>
                <h1 class="font-editorial font-black text-xl uppercase tracking-normal text-black">
                    Set New Password
                </h1>
                <p class="font-sans text-xs text-black/70 mt-2">
                    Create a new, secure password for your account.
                </p>
            </div>

            <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label class="block font-editorial font-bold text-[10px] uppercase tracking-wider text-black mb-1">
                        Email Address
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        value="{{ old('email', $email) }}" 
                        required 
                        placeholder="client@domain.com" 
                        class="w-full border border-black p-3.5 text-xs bg-[#F5F5F0] focus:bg-white focus:outline-none"
                    >
                </div>

                <div>
                    <label class="block font-editorial font-bold text-[10px] uppercase tracking-wider text-black mb-1">
                        New Password
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        required 
                        placeholder="••••••••" 
                        class="w-full border border-black p-3.5 text-xs bg-[#F5F5F0] focus:bg-white focus:outline-none"
                    >
                </div>

                <div>
                    <label class="block font-editorial font-bold text-[10px] uppercase tracking-wider text-black mb-1">
                        Confirm New Password
                    </label>
                    <input 
                        type="password" 
                        name="password_confirmation" 
                        required 
                        placeholder="••••••••" 
                        class="w-full border border-black p-3.5 text-xs bg-[#F5F5F0] focus:bg-white focus:outline-none"
                    >
                </div>

                <button type="submit" class="btn-luxury w-full py-4 text-center text-xs tracking-[0.2em] mt-2 block">
                    Save New Password →
                </button>
            </form>

            <div class="mt-6 text-center border-t border-black/10 pt-4">
                <a href="{{ route('account') }}" class="text-xs font-editorial font-bold uppercase tracking-wider text-black hover:underline">
                    ← Return to Sign In
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
