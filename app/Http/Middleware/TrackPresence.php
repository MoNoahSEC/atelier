<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\UserPresence;
use Closure;
use Illuminate\Http\Request;

class TrackPresence
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->is('api/*') && !$request->is('presence/ping')) {
            $this->record($request);
        }

        return $next($request);
    }

    public static function record(Request $request): void
    {
        try {
            $sessionKey = $request->session()->getId();
            if (!$sessionKey) return;
            $user = $request->user();
            UserPresence::updateOrCreate(
                ['session_key' => $sessionKey],
                [
                    'user_id' => $user?->id,
                    'display_name' => $user?->name,
                    'area' => $request->is('admin/*') ? 'admin' : 'storefront',
                    'page_path' => substr('/' . ltrim($request->path(), '/'), 0, 255),
                    'last_seen_at' => now(),
                ]
            );
        } catch (\Throwable) {
            // Presence must never delay a storefront response.
        }
    }
}
