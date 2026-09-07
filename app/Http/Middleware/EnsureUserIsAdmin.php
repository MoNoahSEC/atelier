<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request: Strict Admin Access Control.
     * Completely eliminates any auto-login backdoor.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // 1. User must be authenticated
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Administrator login required.'
                ], 401);
            }

            return redirect()->route('admin.login')->with('error', 'Authentication required. Please sign in with administrator credentials.');
        }

        $user = Auth::user();

        // 2. User must have administrative privileges
        if (!$user->is_admin) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Administrator privileges required.'
                ], 403);
            }

            abort(403, 'Access Denied: You do not have administrative privileges to access this area.');
        }

        // 3. If specific role constraints are given
        if (!empty($roles) && method_exists($user, 'hasRole') && !$user->hasRole($roles)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Insufficient administrative role permissions.'
                ], 403);
            }

            abort(403, 'Access Denied: Insufficient permissions.');
        }

        return $next($request);
    }
}
