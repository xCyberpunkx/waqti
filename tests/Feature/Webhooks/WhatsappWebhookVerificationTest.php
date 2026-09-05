<?php

namespace Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappWebhookVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_verify_token_returns_the_challenge()
    {
        $response = $this->get('/api/whatsapp/webhook?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'test-verify-token', // matches phpunit.xml
            'hub.challenge' => 'echo-me-back',
        ]));

        $response->assertOk();
        $response->assertSee('echo-me-back', false);
    }

    public function test_wrong_verify_token_is_rejected()
    {
        $response = $this->get('/api/whatsapp/webhook?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'not-the-right-token',
            'hub.challenge' => 'echo-me-back',
        ]));

        $response->assertForbidden();
    }

    public function test_wrong_hub_mode_is_rejected_even_with_the_right_token()
    {
        $response = $this->get('/api/whatsapp/webhook?'.http_build_query([
            'hub.mode' => 'unsubscribe',
            'hub.verify_token' => 'test-verify-token',
            'hub.challenge' => 'echo-me-back',
        ]));

        $response->assertForbidden();
    }

    public function test_missing_params_are_rejected()
    {
        $response = $this->get('/api/whatsapp/webhook');

        $response->assertForbidden();
    }
}
