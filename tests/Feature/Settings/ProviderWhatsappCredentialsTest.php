<?php

namespace Tests\Feature\Settings;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProviderWhatsappCredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_update_whatsapp_credentials()
    {
        $response = $this->patch(route('provider.whatsapp.update'), [
            'whatsapp_phone_number_id' => '123',
            'whatsapp_business_account_id' => '456',
            'whatsapp_access_token' => 'secret-token',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_whatsapp_credentials_can_be_stored_on_first_save()
    {
        $user = User::factory()->create();
        Provider::factory()->for($user)->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('provider.whatsapp.update'), [
                'whatsapp_phone_number_id' => '1234567890',
                'whatsapp_business_account_id' => '0987654321',
                'whatsapp_access_token' => 'super-secret-token',
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame(
            'super-secret-token',
            $user->provider->fresh()->whatsapp_access_token,
        );
    }

    public function test_access_token_is_required_the_first_time()
    {
        $user = User::factory()->create();
        Provider::factory()->for($user)->create(['whatsapp_access_token' => null]);

        $response = $this
            ->actingAs($user)
            ->patch(route('provider.whatsapp.update'), [
                'whatsapp_phone_number_id' => '1234567890',
                'whatsapp_business_account_id' => '0987654321',
            ]);

        $response->assertSessionHasErrors('whatsapp_access_token');
    }

    public function test_access_token_is_not_required_to_update_other_fields_once_set()
    {
        $user = User::factory()->create();
        $provider = Provider::factory()->for($user)->withWhatsappCredentials()->create();
        $originalToken = $provider->whatsapp_access_token;

        $response = $this
            ->actingAs($user)
            ->patch(route('provider.whatsapp.update'), [
                'whatsapp_phone_number_id' => 'new-phone-number-id',
                'whatsapp_business_account_id' => $provider->whatsapp_business_account_id,
            ]);

        $response->assertSessionHasNoErrors();

        $provider->refresh();
        $this->assertSame('new-phone-number-id', $provider->whatsapp_phone_number_id);
        $this->assertSame($originalToken, $provider->whatsapp_access_token);
    }

    public function test_whatsapp_access_token_is_encrypted_at_rest()
    {
        $user = User::factory()->create();
        Provider::factory()->for($user)->create();

        $this
            ->actingAs($user)
            ->patch(route('provider.whatsapp.update'), [
                'whatsapp_phone_number_id' => '1234567890',
                'whatsapp_business_account_id' => '0987654321',
                'whatsapp_access_token' => 'plaintext-should-not-appear-in-db',
            ]);

        $rawValue = DB::table('providers')
            ->where('user_id', $user->id)
            ->value('whatsapp_access_token');

        $this->assertNotNull($rawValue);
        $this->assertStringNotContainsString('plaintext-should-not-appear-in-db', $rawValue);
    }

    public function test_provider_settings_page_never_exposes_the_raw_access_token()
    {
        $user = User::factory()->create();
        Provider::factory()->for($user)->withWhatsappCredentials()->create();

        $response = $this->actingAs($user)->get(route('provider.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('provider.has_whatsapp_access_token', true)
            ->missing('provider.whatsapp_access_token'));
    }

    public function test_credentials_update_ignores_a_spoofed_user_id()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Provider::factory()->for($user)->create();
        Provider::factory()->for($otherUser)->create();

        $this
            ->actingAs($user)
            ->patch(route('provider.whatsapp.update'), [
                'whatsapp_phone_number_id' => '1234567890',
                'whatsapp_business_account_id' => '0987654321',
                'whatsapp_access_token' => 'secret-token',
                'user_id' => $otherUser->id,
            ]);

        $this->assertNotSame(
            'secret-token',
            $otherUser->provider->fresh()->whatsapp_access_token,
        );
    }
}
