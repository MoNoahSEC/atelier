<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $isMaintenance = in_array((string) Setting::get('maintenance_mode', '0'), ['1', 'true', 'on'], true);

        if ($isMaintenance) {
            // Admins bypass maintenance mode
            if (Auth::check() && Auth::user()->isAdmin()) {
                return $next($request);
            }

            // Exclude admin auth routes so admins can still log in
            if ($request->is('admin*')) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store is undergoing scheduled archive maintenance. We will return shortly.'
                ], 503);
            }

            return response()->view('maintenance', [
                'settings' => Setting::allAsMap(),
            ], 503);
        }

        return $next($request);
    }
}
