<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Provider>
 */
class ProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'business_category' => fake()->randomElement([
                'barbershop', 'salon', 'clinic', 'gym', 'consulting',
            ]),
            'timezone' => 'Africa/Algiers',
            'whatsapp_phone_number_id' => null,
            'whatsapp_business_account_id' => null,
            'whatsapp_access_token' => null,
        ];
    }

    /**
     * Indicate the provider has WhatsApp Cloud API credentials stored.
     */
    public function withWhatsappCredentials(): static
    {
        return $this->state(fn (array $attributes) => [
            'whatsapp_phone_number_id' => fake()->numerify('##############'),
            'whatsapp_business_account_id' => fake()->numerify('##############'),
            'whatsapp_access_token' => fake()->sha256(),
        ]);
    }
}
