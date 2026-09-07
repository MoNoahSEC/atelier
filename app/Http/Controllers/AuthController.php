<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $throttleKey = \Illuminate\Support\Str::transliterate(\Illuminate\Support\Str::lower($request->input('email')).'|'.$request->ip());

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            $message = "Too many login attempts. Please try again in {$seconds} seconds.";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 429);
            }
            return back()->withErrors(['email' => $message])->onlyInput('email');
        }

        if (Auth::attempt($credentials)) {
            \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            
            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }
            
            return redirect()->intended('/account')->with('success', 'Signed in successfully.');
        }

        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials. Please try again.'
            ], 401);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function register(Request $request)
    {
        return redirect()->route('auth.google')->with('info', 'Customer accounts are created and authenticated exclusively via Google Sign-In.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
