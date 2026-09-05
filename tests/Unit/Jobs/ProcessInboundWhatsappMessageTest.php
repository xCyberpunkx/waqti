<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessInboundWhatsappMessage;
use App\Models\Client;
use App\Models\ConversationState;
use App\Models\InboundMessage;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ProcessInboundWhatsappMessageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides  Merged into the single message object.
     */
    private function payloadWithMessage(string $phoneNumberId, array $overrides = []): array
    {
        $message = array_merge([
            'from' => '213555000111',
            'id' => 'wamid.TEST123',
            'timestamp' => (string) now()->timestamp,
            'type' => 'text',
            'text' => ['body' => 'Hello, is Saturday free?'],
        ], $overrides);

        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'entry-1',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => ['phone_number_id' => $phoneNumberId],
                                'contacts' => [
                                    ['profile' => ['name' => 'Amine'], 'wa_id' => '213555000111'],
                                ],
                                'messages' => [$message],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_processing_a_new_message_creates_client_inbound_message_and_conversation_state()
    {
        $provider = Provider::factory()->create(['whatsapp_phone_number_id' => '999888777']);

        (new ProcessInboundWhatsappMessage($this->payloadWithMessage('999888777')))->handle();

        $client = Client::where('provider_id', $provider->id)
            ->where('phone_number', '213555000111')
            ->first();

        $this->assertNotNull($client);
        $this->assertSame('Amine', $client->display_name);

        $this->assertDatabaseHas('inbound_messages', [
            'provider_id' => $provider->id,
            'client_id' => $client->id,
            'whatsapp_message_id' => 'wamid.TEST123',
            'body' => 'Hello, is Saturday free?',
        ]);

        $state = ConversationState::where('client_id', $client->id)->first();
        $this->assertNotNull($state);
        $this->assertSame(ConversationState::DEFAULT_STATE, $state->state_key);
    }

    public function test_processing_the_same_message_id_twice_does_not_duplicate_anything()
    {
        $provider = Provider::factory()->create(['whatsapp_phone_number_id' => '999888777']);
        $payload = $this->payloadWithMessage('999888777');

        (new ProcessInboundWhatsappMessage($payload))->handle();
        (new ProcessInboundWhatsappMessage($payload))->handle();

        $this->assertSame(1, InboundMessage::where('whatsapp_message_id', 'wamid.TEST123')->count());
        $this->assertSame(1, Client::where('provider_id', $provider->id)->count());
        $this->assertSame(1, ConversationState::where('provider_id', $provider->id)->count());
    }

    public function test_a_second_message_from_the_same_client_reuses_the_existing_client_and_conversation_state()
    {
        $provider = Provider::factory()->create(['whatsapp_phone_number_id' => '999888777']);

        (new ProcessInboundWhatsappMessage(
            $this->payloadWithMessage('999888777', ['id' => 'wamid.FIRST'])
        ))->handle();

        (new ProcessInboundWhatsappMessage(
            $this->payloadWithMessage('999888777', ['id' => 'wamid.SECOND'])
        ))->handle();

        $this->assertSame(1, Client::where('provider_id', $provider->id)->count());
        $this->assertSame(1, ConversationState::where('provider_id', $provider->id)->count());
        $this->assertSame(2, InboundMessage::where('provider_id', $provider->id)->count());
    }

    public function test_an_unknown_phone_number_id_is_logged_and_skipped_without_throwing()
    {
        Log::spy();

        (new ProcessInboundWhatsappMessage($this->payloadWithMessage('no-such-number')))->handle();

        $this->assertSame(0, InboundMessage::count());
        $this->assertSame(0, Client::count());
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_a_message_with_no_text_body_is_still_stored()
    {
        $provider = Provider::factory()->create(['whatsapp_phone_number_id' => '999888777']);

        $payload = $this->payloadWithMessage('999888777', ['type' => 'image', 'text' => null]);
        unset($payload['entry'][0]['changes'][0]['value']['messages'][0]['text']);

        (new ProcessInboundWhatsappMessage($payload))->handle();

        $this->assertDatabaseHas('inbound_messages', [
            'provider_id' => $provider->id,
            'whatsapp_message_id' => 'wamid.TEST123',
            'body' => null,
        ]);
    }

    public function test_a_delivery_with_multiple_messages_processes_each_one()
    {
        $provider = Provider::factory()->create(['whatsapp_phone_number_id' => '999888777']);

        $payload = $this->payloadWithMessage('999888777');
        $payload['entry'][0]['changes'][0]['value']['messages'][] = [
            'from' => '213555000222',
            'id' => 'wamid.OTHER',
            'timestamp' => (string) now()->timestamp,
            'type' => 'text',
            'text' => ['body' => 'Me too please'],
        ];
        $payload['entry'][0]['changes'][0]['value']['contacts'][] = [
            'profile' => ['name' => 'Sara'],
            'wa_id' => '213555000222',
        ];

        (new ProcessInboundWhatsappMessage($payload))->handle();

        $this->assertSame(2, InboundMessage::where('provider_id', $provider->id)->count());
        $this->assertSame(2, Client::where('provider_id', $provider->id)->count());
    }

    public function test_each_provider_only_sees_their_own_clients_for_the_same_phone_number()
    {
        $providerA = Provider::factory()->create(['whatsapp_phone_number_id' => 'A111']);
        $providerB = Provider::factory()->create(['whatsapp_phone_number_id' => 'B222']);

        (new ProcessInboundWhatsappMessage(
            $this->payloadWithMessage('A111', ['id' => 'wamid.A'])
        ))->handle();
        (new ProcessInboundWhatsappMessage(
            $this->payloadWithMessage('B222', ['id' => 'wamid.B'])
        ))->handle();

        $this->assertSame(1, Client::where('provider_id', $providerA->id)->count());
        $this->assertSame(1, Client::where('provider_id', $providerB->id)->count());
        $this->assertNotSame(
            Client::where('provider_id', $providerA->id)->first()->id,
            Client::where('provider_id', $providerB->id)->first()->id,
        );
    }
}
