<?php

namespace App\Models;

use Database\Factories\SlotExceptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A one-off closure or extra-hours override for a specific calendar
 * date, layered on top of the recurring AvailabilityRule for that
 * weekday. No `updated_at` — see DATABASE_SCHEMA.md §3 and the
 * migration.
 *
 * @property int $id
 * @property int $provider_id
 * @property Carbon $date
 * @property bool $is_closed
 * @property string|null $override_starts_at
 * @property string|null $override_ends_at
 * @property string|null $reason
 * @property Carbon|null $created_at
 */
#[Fillable(['date', 'is_closed', 'override_starts_at', 'override_ends_at', 'reason'])]
class SlotException extends Model
{
    /** @use HasFactory<SlotExceptionFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_closed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
