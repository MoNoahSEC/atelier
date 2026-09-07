<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAutomationHmac
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('automation.hmac.secret');
        if (empty($secret)) {
            // If no secret is configured, bypass HMAC check.
            // In production, this should be set.
            return $next($request);
        }

        $signatureHeader = config('automation.hmac.signature_header', 'X-Automation-Signature');
        $timestampHeader = config('automation.hmac.timestamp_header', 'X-Automation-Timestamp');
        $ttl = config('automation.hmac.timestamp_ttl', 300);

        $signature = $request->header($signatureHeader);
        $timestamp = (int) $request->header($timestampHeader);

        if (! $signature || ! $timestamp) {
            return response()->json(['message' => 'Missing HMAC signature or timestamp.'], 401);
        }

        // Prevent replay attacks
        if (abs(time() - $timestamp) > $ttl) {
            return response()->json(['message' => 'Request timestamp is outside the allowed window (possible replay attack).'], 401);
        }

        $payload = $timestamp . '.' . $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json(['message' => 'Invalid HMAC signature.'], 401);
        }

        return $next($request);
    }
}
