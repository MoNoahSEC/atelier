<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\TrackPresence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresenceController extends Controller
{
    public function ping(Request $request): JsonResponse
    {
        TrackPresence::record($request);
        return response()->json(['ok' => true]);
    }
}
