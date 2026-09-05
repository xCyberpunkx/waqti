<?php

namespace App\Models;

use Database\Factories\ConversationStateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The current step of a Client's in-progress booking flow — per
 * DOMAIN_MODEL.md §4. Skeleton only for Step 3: this class holds
 * whatever `state_key` is written to it, but defines no actual states,
 * no transition rules, and no menu/parsing logic. That's Step 4 (the
 * booking flow) — do not add business logic here ahead of that.
 *
 * No `created_at` — see the migration.
 *
 * @property int $id
 * @property int $client_id
 * @property int $provider_id
 * @property string $state_key
 * @property array<string, mixed>|null $context_json
 * @property Carbon|null $updated_at
 */
#[Fillable(['client_id', 'provider_id', 'state_key', 'context_json'])]
class ConversationState extends Model
{
    /** @use HasFactory<ConversationStateFactory> */
    use HasFactory;

    const CREATED_AT = null;

    /**
     * The default state for a client with no in-progress flow. Step 4
     * introduces the real state vocabulary (e.g.
     * `awaiting_service_selection`) — this is just "nothing is
     * happening yet."
     */
    const DEFAULT_STATE = 'idle';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
