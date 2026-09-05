<?php

namespace App\Models;

use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Per DOMAIN_MODEL.md §3 / §7. Only the schema and the read-side
 * `active()` scope (needed for slot-exclusion, see
 * App\Actions\Availability\ComputeAvailableSlots) are built in Step 2.
 * Creation, the state-machine transitions, and double-booking
 * prevention under concurrency are Step 4 — do not add mutation logic
 * here ahead of that.
 *
 * @property int $id
 * @property int $provider_id
 * @property int $client_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string $status
 * @property string $source
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['starts_at', 'ends_at', 'status', 'source', 'cancelled_at', 'cancellation_reason'])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * A Booking is terminal in these states — see DOMAIN_MODEL.md §7.
     * Statuses that still occupy a slot and must be excluded from
     * available-slot computation.
     *
     * @var list<string>
     */
    const ACTIVE_STATUSES = ['pending', 'confirmed'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    /**
     * Bookings that still occupy their slot (pending/confirmed) — the
     * ones slot computation must exclude. Cancelled/completed/no_show
     * bookings free the slot back up.
     *
     * @param  Builder<Booking>  $query
     * @return Builder<Booking>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }
}
