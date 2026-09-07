<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * Display the form to request a password reset link.
     */
    public function showForgotForm(): View
    {
        $settings = Setting::allAsMap();
        return view('auth.forgot-password', compact('settings'));
    }

    /**
     * Send a password reset link to the user.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'We could not find an account with that email address.',
        ]);

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->update([
                'password_reset_requested_at' => now(),
                'password_reset_requested_by' => 'User',
            ]);
        }

        // Send reset link using Laravel broker
        $status = Password::broker()->sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()->withErrors(['email' => __($status)]);
    }

    /**
     * Display the password reset form.
     */
    public function showResetForm(Request $request, string $token): View
    {
        $settings = Setting::allAsMap();
        $email = $request->email ?? '';
        return view('auth.reset-password', compact('settings', 'token', 'email'));
    }

    /**
     * Reset the user's password.
     */
    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'            => Hash::make($password),
                    'remember_token'      => Str::random(60),
                    'password_changed_at' => now(),
                ])->save();

                event(new PasswordReset($user));

                Auth::login($user);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $request->session()->regenerate();
            return redirect()->route('account')->with('success', 'Your password has been successfully reset.');
        }

        return back()->withErrors(['email' => __($status)]);
    }

    /**
     * Admin-triggered password reset email.
     * Admin NEVER sees the plaintext password.
     */
    public function adminSendResetLink(Request $request, int $userId): JsonResponse|RedirectResponse
    {
        $user = User::findOrFail($userId);

        $user->update([
            'password_reset_requested_at' => now(),
            'password_reset_requested_by' => 'Admin',
        ]);

        $status = Password::broker()->sendResetLink(['email' => $user->email]);
        $timestamp = now()->format('Y-m-d H:i:s');
        $message = "Reset email sent to {$user->email} at {$timestamp}";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'   => $status === Password::RESET_LINK_SENT,
                'message'   => $message,
                'timestamp' => $timestamp,
                'status'    => __($status),
            ]);
        }

        return back()->with('success', $message);
    }
}
