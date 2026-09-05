<?php

namespace Tests\Feature\Webhooks;

use App\Jobs\ProcessInboundWhatsappMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class WhatsappWebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    private function samplePayload(): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '123',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => ['phone_number_id' => '999'],
                                'messages' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function signatureFor(string $body): string
    {
        // Matches phpunit.xml's WHATSAPP_APP_SECRET.
        return 'sha256='.hash_hmac('sha256', $body, 'test-app-secret-do-not-use-in-production');
    }

    public function test_a_request_with_no_signature_header_is_rejected()
    {
        Bus::fake();

        $response = $this->postJson('/api/whatsapp/webhook', $this->samplePayload());

        $response->assertForbidden();
        Bus::assertNotDispatched(ProcessInboundWhatsappMessage::class);
    }

    public function test_a_request_with_an_invalid_signature_is_rejected()
    {
        Bus::fake();

        $response = $this->postJson('/api/whatsapp/webhook', $this->samplePayload(), [
            'X-Hub-Signature-256' => 'sha256=0000000000000000000000000000000000000000000000000000000000000000',
        ]);

        $response->assertForbidden();
        Bus::assertNotDispatched(ProcessInboundWhatsappMessage::class);
    }

    public function test_a_request_with_a_malformed_signature_header_is_rejected()
    {
        Bus::fake();

        $response = $this->postJson('/api/whatsapp/webhook', $this->samplePayload(), [
            'X-Hub-Signature-256' => 'not-even-the-right-format',
        ]);

        $response->assertForbidden();
        Bus::assertNotDispatched(ProcessInboundWhatsappMessage::class);
    }

    public function test_a_request_with_a_valid_signature_is_accepted_and_queues_processing()
    {
        Bus::fake();

        $body = json_encode($this->samplePayload());

        $response = $this->call(
            'POST',
            '/api/whatsapp/webhook',
            server: ['HTTP_X-Hub-Signature-256' => $this->signatureFor($body)],
            content: $body,
        );

        $response->assertOk();
        Bus::assertDispatched(ProcessInboundWhatsappMessage::class);
    }
}
