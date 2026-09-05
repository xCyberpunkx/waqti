<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\InboundMessage;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InboundMessage>
 */
class InboundMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'client_id' => Client::factory(),
            'whatsapp_message_id' => 'wamid.'.fake()->unique()->uuid(),
            'body' => fake()->sentence(),
            'payload_json' => ['type' => 'text', 'text' => ['body' => 'test']],
            'conversation_state' => 'idle',
            'received_at' => now(),
            'processed_at' => now(),
        ];
    }
}
