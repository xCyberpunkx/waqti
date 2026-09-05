<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ConversationState;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversationState>
 */
class ConversationStateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'provider_id' => Provider::factory(),
            'state_key' => ConversationState::DEFAULT_STATE,
            'context_json' => null,
        ];
    }
}
