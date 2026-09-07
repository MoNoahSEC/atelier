<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GoogleAuthController extends Controller
{
    /**
     * Redirect client to Google OAuth Consent Screen.
     * If credentials are not yet configured, directs to the visual setup guide.
     */
    public function redirect(): RedirectResponse
    {
        $clientId = config('services.google.client_id') ?: (env('GOOGLE_CLIENT_ID') ?: Setting::get('google_client_id'));

        if (empty($clientId) || $clientId === 'your-google-client-id') {
            return redirect()->route('auth.google.setup');
        }

        $state = Str::random(40);
        session(['google_oauth_state' => $state]);

        $redirectUri = $this->resolveRedirectUri();

        $query = http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid profile email',
            'access_type'   => 'offline',
            'state'         => $state,
            'prompt'        => 'select_account',
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    /**
     * Handle the OAuth callback from Google.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect()->route('account')->with('error', 'Google Sign-In was cancelled.');
        }

        $state = $request->input('state');
        if (empty($state) || $state !== session('google_oauth_state')) {
            return redirect()->route('account')->with('error', 'Authentication security token mismatch. Please try again.');
        }
        session()->forget('google_oauth_state');

        $code = $request->input('code');
        if (empty($code)) {
            return redirect()->route('account')->with('error', 'No authorization code received from Google.');
        }

        $clientId     = config('services.google.client_id') ?: (env('GOOGLE_CLIENT_ID') ?: Setting::get('google_client_id'));
        $clientSecret = config('services.google.client_secret') ?: (env('GOOGLE_CLIENT_SECRET') ?: Setting::get('google_client_secret'));
        $redirectUri  = $this->resolveRedirectUri();

        try {
            // 1. Exchange code for tokens
            $tokenRes = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code'          => $code,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri'  => $redirectUri,
                'grant_type'    => 'authorization_code',
            ]);

            if (!$tokenRes->successful()) {
                return redirect()->route('account')->with('error', 'Failed to exchange token with Google: ' . $tokenRes->body());
            }

            $tokenData   = $tokenRes->json();
            $accessToken = $tokenData['access_token'] ?? null;

            if (!$accessToken) {
                return redirect()->route('account')->with('error', 'Invalid access token received.');
            }

            // 2. Fetch User Profile
            $userRes = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if (!$userRes->successful()) {
                return redirect()->route('account')->with('error', 'Failed to fetch profile information from Google.');
            }

            $googleProfile = $userRes->json();
            $email = $googleProfile['email'] ?? null;
            $name  = $googleProfile['name']  ?? 'Google Client';

            if (!$email) {
                return redirect()->route('account')->with('error', 'No verified email returned from Google account.');
            }

            // 3. Find or Create User
            $cleanEmail = mb_strtolower(trim($email));
            $user = User::firstOrCreate(
                ['email' => $cleanEmail],
                [
                    'name'     => $name,
                    'password' => Hash::make(Str::random(32)),
                ]
            );

            // 4. Check if this Google account is designated as an Administrator
            $adminEmailsRaw = Setting::get('admin_google_emails', '') . ',' . (env('ADMIN_GOOGLE_EMAILS') ?: '');
            $adminEmails = array_filter(array_map('trim', explode(',', str_replace(["\r\n", "\n", ";"], ',', mb_strtolower($adminEmailsRaw)))));

            $isDesignatedAdmin = in_array($cleanEmail, $adminEmails, true);
            if ($isDesignatedAdmin && !$user->is_admin) {
                $user->is_admin = true;
                $user->admin_role = 'super_admin';
                $user->save();
            }

            // Log in user
            Auth::login($user, true);
            $request->session()->regenerate();

            if ($user->is_admin) {
                return redirect('/admin')->with('success', "مرحباً بك يا {$user->name}! تم تسجيل دخولك بنجاح كمسؤول للمتجر عبر حساب Google.");
            }

            return redirect()->intended('/account')->with('success', "Welcome, {$user->name}! Signed in via Google.");
        } catch (\Throwable $e) {
            return redirect()->route('account')->with('error', 'An error occurred during Google sign-in: ' . $e->getMessage());
        }
    }

    /**
     * Resolve the OAuth redirect URI dynamically.
     * Always matches http://localhost:8000/auth/google/callback to prevent 127.0.0.1 mismatch.
     */
    private function resolveRedirectUri(): string
    {
        $custom = env('GOOGLE_REDIRECT_URI') ?: config('services.google.redirect');
        if (!empty($custom) && str_starts_with($custom, 'http')) {
            return $custom;
        }

        // Dynamically resolve using current request protocol and domain
        return route('auth.google.callback');
    }

    /**
     * Show detailed, bilingual step-by-step setup guide for connecting Google Cloud Console OAuth.
     */
    public function setup(): View
    {
        $settings    = Setting::allAsMap();
        $callbackUrl = $this->resolveRedirectUri();
        $siteUrl     = url('/');

        return view('auth.google-setup', compact('settings', 'callbackUrl', 'siteUrl'));
    }
}
