<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAutoAuth
{
    /**
     * Handle an incoming request for Admin API with strict authentication.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum') ?: Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Administrator login required.'
            ], 401);
        }

        if (!$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Administrator privileges required.'
            ], 403);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
