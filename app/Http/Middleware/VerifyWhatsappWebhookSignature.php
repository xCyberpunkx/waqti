<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per SECURITY.md §4: "Every inbound webhook request must have its
 * X-Hub-Signature-256 verified against the app secret before any
 * processing occurs. A request that fails verification is rejected,
 * not logged-and-processed" — so this runs before the controller ever
 * sees the request, and never writes anything (not even a log entry)
 * about a request that fails.
 */
class VerifyWhatsappWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $appSecret = config('services.whatsapp.app_secret');
        $header = (string) $request->header('X-Hub-Signature-256', '');

        if (
            $appSecret === null
            || $appSecret === ''
            || ! str_starts_with($header, 'sha256=')
        ) {
            abort(403);
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        // Timing-safe comparison — a straight `===` leaks timing
        // information about how many leading bytes matched.
        if (! hash_equals($expected, $header)) {
            abort(403);
        }

        return $next($request);
    }
}
