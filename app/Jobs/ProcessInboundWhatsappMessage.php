<?php

namespace App\Jobs;

use App\Models\ConversationState;
use App\Models\InboundMessage;
use App\Models\Provider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Processes one WhatsApp webhook delivery payload. Dispatched by
 * WhatsappWebhookController after signature verification, so the
 * controller can acknowledge Meta immediately (WHATSAPP_INTEGRATION.md
 * §6: "queued immediately and acknowledged fast").
 *
 * A single delivery can contain multiple entries/changes/messages
 * (Meta batches), so this loops rather than assuming exactly one.
 *
 * Step 3 scope only: extracts and stores each message, resolves/creates
 * the Client, and ensures a ConversationState row exists. It does NOT
 * interpret message content, drive any menu, or transition states based
 * on what was said — that's Step 4 (the booking flow). Writing business
 * logic into this job ahead of that would violate the required
 * implementation sequence in CLAUDE_HANDOFF.md.
 */
class ProcessInboundWhatsappMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload  The raw, already
     *                                         signature-verified webhook body.
     */
    public function __construct(public readonly array $payload) {}

    public function handle(): void
    {
        foreach ($this->payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $this->processChange($change['value'] ?? []);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function processChange(array $value): void
    {
        $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

        if ($phoneNumberId === null) {
            return;
        }

        $provider = Provider::where('whatsapp_phone_number_id', $phoneNumberId)->first();

        // A webhook for a phone number ID that isn't provisioned to any
        // provider we know about — nothing to attach it to. Log and
        // move on rather than throwing, since one bad entry in a batch
        // shouldn't fail the other, valid ones in the same delivery.
        if ($provider === null) {
            Log::warning('WhatsApp webhook: no provider for phone_number_id', [
                'phone_number_id' => $phoneNumberId,
            ]);

            return;
        }

        $contactsByWaId = collect($value['contacts'] ?? [])->keyBy('wa_id');

        foreach ($value['messages'] ?? [] as $message) {
            $this->processMessage($provider, $message, $contactsByWaId);
        }
    }

    /**
     * @param  array<string, mixed>  $message
     * @param  Collection<string, array<string, mixed>>  $contactsByWaId
     */
    private function processMessage(Provider $provider, array $message, Collection $contactsByWaId): void
    {
        $whatsappMessageId = $message['id'] ?? null;

        if ($whatsappMessageId === null) {
            return;
        }

        // Idempotency — SOURCE_OF_TRUTH.md §2.3, SECURITY.md §5. A
        // redelivered message must be a complete no-op: no new Client,
        // no ConversationState touch, nothing.
        if (InboundMessage::where('whatsapp_message_id', $whatsappMessageId)->exists()) {
            return;
        }

        $fromPhoneNumber = $message['from'] ?? null;

        if ($fromPhoneNumber === null) {
            return;
        }

        $displayName = $contactsByWaId->get($fromPhoneNumber)['profile']['name'] ?? null;

        $client = $provider->clients()->firstOrCreate(
            ['phone_number' => $fromPhoneNumber],
            ['display_name' => $displayName, 'first_contacted_at' => now()],
        );

        $conversationState = $client->conversationState()->firstOrCreate(
            [],
            ['provider_id' => $provider->id, 'state_key' => ConversationState::DEFAULT_STATE],
        );

        $body = $message['text']['body'] ?? null;

        $timestamp = isset($message['timestamp'])
            ? Carbon::createFromTimestamp((int) $message['timestamp'])
            : now();

        // A duplicate delivery racing this same job (two webhook
        // deliveries for the same message processed concurrently) would
        // otherwise violate the unique constraint here — that's the
        // backstop the `exists()` check above is a fast-path for, not
        // a replacement for.
        //
        // `provider_id` is set explicitly (not purely relation-derived)
        // because this row legitimately needs both FKs and there's no
        // single relation that provides both at once — that's fine
        // here specifically because the source is this job's own
        // parsed, already-authenticated payload, not raw HTTP request
        // input (the actual boundary SECURITY.md §7 is protecting).
        try {
            $client->inboundMessages()->create([
                'provider_id' => $provider->id,
                'whatsapp_message_id' => $whatsappMessageId,
                'body' => $body,
                'payload_json' => $message,
                'conversation_state' => $conversationState->state_key,
                'received_at' => $timestamp,
                'processed_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Already processed by a concurrent delivery of the same
            // message — the whole point of idempotency is that this is
            // a silent no-op, not an error.
        }
    }
}
