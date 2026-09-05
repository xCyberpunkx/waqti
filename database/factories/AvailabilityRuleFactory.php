<?php

namespace Database\Factories;

use App\Models\AvailabilityRule;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvailabilityRule>
 */
class AvailabilityRuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'weekday' => fake()->numberBetween(0, 6),
            'starts_at' => '09:00',
            'ends_at' => '18:00',
            'slot_length_minutes' => 30,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
