<?php

namespace App\Models;

use Database\Factories\AvailabilityRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A recurring weekly working-hours pattern for one weekday. Phase 1: at
 * most one rule per (provider, weekday) — see the migration.
 *
 * @property int $id
 * @property int $provider_id
 * @property int $weekday
 * @property string $starts_at
 * @property string $ends_at
 * @property int $slot_length_minutes
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['weekday', 'starts_at', 'ends_at', 'slot_length_minutes', 'is_active'])]
class AvailabilityRule extends Model
{
    /** @use HasFactory<AvailabilityRuleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'slot_length_minutes' => 'integer',
            'is_active' => 'boolean',
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
