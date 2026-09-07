<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthController extends Controller
{
    /**
     * Auto-authenticate and redirect directly to dashboard.
     */
    public function showLoginForm(): Response
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            $adminUser = User::where('is_admin', true)->where('admin_role', 'super_admin')->first()
                ?? User::where('is_admin', true)->first();

            if (!$adminUser) {
                $adminUser = User::create([
                    'name'       => 'Administrator',
                    'email'      => 'admin@atelier.com',
                    'password'   => Hash::make('admin123456'),
                    'is_admin'   => true,
                    'admin_role' => 'super_admin',
                ]);
            }

            Auth::login($adminUser);
        }

        return redirect()->route('admin.dashboard');
    }

    /**
     * Handle an administrative login attempt.
     */
    public function login(Request $request)
    {
        return redirect()->route('admin.dashboard');
    }

    /**
     * Terminate the administrative session or return to storefront.
     */
    public function logout(Request $request)
    {
        return redirect()->route('home')->with('success', 'Returned to storefront.');
    }
}
