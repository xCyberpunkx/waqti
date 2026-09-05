<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\SlotException;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlotException>
 */
class SlotExceptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'date' => now()->addDay()->toDateString(),
            'is_closed' => true,
            'override_starts_at' => null,
            'override_ends_at' => null,
            'reason' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'is_closed' => true,
            'override_starts_at' => null,
            'override_ends_at' => null,
        ]);
    }

    public function extraHours(string $startsAt = '09:00', string $endsAt = '13:00'): static
    {
        return $this->state(fn () => [
            'is_closed' => false,
            'override_starts_at' => $startsAt,
            'override_ends_at' => $endsAt,
        ]);
    }
}
