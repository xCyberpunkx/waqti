<?php

namespace App\Models;

use Database\Factories\InboundMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A message received from a Client via the WhatsApp webhook. Per
 * DOMAIN_MODEL.md §4 — this is a log/idempotency record, not something
 * mutated after creation except to stamp `processed_at`. No `created_at`
 * — `received_at` serves that purpose (set from Meta's own timestamp
 * where available), and there's no `updated_at` either since nothing
 * here changes except the one `processed_at` stamp.
 *
 * @property int $id
 * @property int $provider_id
 * @property int $client_id
 * @property string $whatsapp_message_id
 * @property string|null $body
 * @property array<string, mixed> $payload_json
 * @property string|null $conversation_state
 * @property Carbon $received_at
 * @property Carbon|null $processed_at
 */
#[Fillable(['provider_id', 'client_id', 'whatsapp_message_id', 'body', 'payload_json', 'conversation_state', 'received_at', 'processed_at'])]
class InboundMessage extends Model
{
    /** @use HasFactory<InboundMessageFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
