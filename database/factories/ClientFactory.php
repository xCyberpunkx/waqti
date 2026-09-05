<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'phone_number' => '+2136' . fake()->numerify('########'),
            'display_name' => fake()->firstName(),
            'consent_status' => 'unknown',
            'first_contacted_at' => now(),
        ];
    }
}
