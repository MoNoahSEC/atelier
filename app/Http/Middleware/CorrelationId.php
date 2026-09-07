<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationId
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

        // Add to request for downstream usage
        $request->headers->set('X-Correlation-ID', $correlationId);

        // Make it available in logs (if using Log context)
        // Log::withContext(['correlation_id' => $correlationId]);

        /** @var Response $response */
        $response = $next($request);

        // Include in response
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
