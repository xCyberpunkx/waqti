<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDay()->setTime(10, 0);

        return [
            'provider_id' => Provider::factory(),
            'client_id' => Client::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
            'status' => 'confirmed',
            'source' => 'manual',
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }
}
