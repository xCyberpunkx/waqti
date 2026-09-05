<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessInboundWhatsappMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Per SOURCE_OF_TRUTH.md §2.8: "Webhook handlers are controllers too —
 * the same rule applies" (thin controller, no business logic here).
 * Signature verification happens in VerifyWhatsappWebhookSignature,
 * before this class is ever reached for `handle()`. Actual message
 * processing happens in ProcessInboundWhatsappMessage, off the request
 * cycle entirely — see that job's docblock for what is and isn't in
 * scope for Step 3.
 */
class WhatsappWebhookController extends Controller
{
    /**
     * Meta's one-time webhook subscription handshake. Note: Meta sends
     * `hub.mode`, `hub.verify_token`, `hub.challenge` as query params,
     * but PHP automatically rewrites dots in query-string keys to
     * underscores — this isn't a typo, `hub_mode` really is what
     * arrives in $_GET for a query string of `hub.mode=...`.
     */
    public function verify(Request $request): Response
    {
        $token = config('services.whatsapp.webhook_verify_token');

        if (
            $token !== null
            && $token !== ''
            && $request->query('hub_mode') === 'subscribe'
            && hash_equals($token, (string) $request->query('hub_verify_token', ''))
        ) {
            return response((string) $request->query('hub_challenge', ''), 200)
                ->header('Content-Type', 'text/plain');
        }

        abort(403);
    }

    /**
     * Inbound message delivery. Acknowledges immediately and queues the
     * actual work — WHATSAPP_INTEGRATION.md §6: "Every inbound webhook
     * delivery is queued immediately and acknowledged fast (Meta
     * expects a quick 200)."
     */
    public function handle(Request $request): JsonResponse
    {
        ProcessInboundWhatsappMessage::dispatch($request->all());

        return response()->json(['status' => 'ok']);
    }
}
