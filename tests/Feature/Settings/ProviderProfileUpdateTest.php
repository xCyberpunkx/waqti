<?php

namespace Tests\Feature\Settings;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_provider_settings()
    {
        $response = $this->get(route('provider.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_provider_settings_page_is_displayed_with_no_provider_yet()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('provider.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('provider', null));
    }

    public function test_provider_settings_page_shows_only_the_authenticated_users_provider()
    {
        $user = User::factory()->create();
        Provider::factory()->for($user)->create(['name' => 'My Business']);
        Provider::factory()->create(['name' => 'Someone Elses Business']);

        $response = $this->actingAs($user)->get(route('provider.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('provider.name', 'My Business'));
    }

    public function test_provider_profile_is_created_on_first_save()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('provider.update'), [
                'name' => 'Blida Barbershop',
                'business_category' => 'barbershop',
                'timezone' => 'Africa/Algiers',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('provider.edit'));

        $this->assertDatabaseHas('providers', [
            'user_id' => $user->id,
            'name' => 'Blida Barbershop',
            'business_category' => 'barbershop',
            'timezone' => 'Africa/Algiers',
        ]);
    }

    public function test_provider_profile_can_be_updated()
    {
        $user = User::factory()->create();
        $provider = Provider::factory()->for($user)->create(['name' => 'Old Name']);

        $response = $this
            ->actingAs($user)
            ->patch(route('provider.update'), [
                'name' => 'New Name',
                'business_category' => $provider->business_category,
                'timezone' => 'Europe/Paris',
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame('New Name', $provider->fresh()->name);
        $this->assertSame('Europe/Paris', $provider->fresh()->timezone);
    }

    public function test_provider_creation_ignores_a_spoofed_user_id_in_the_request()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this
            ->actingAs($user)
            ->patch(route('provider.update'), [
                'name' => 'Blida Barbershop',
                'timezone' => 'Africa/Algiers',
                'user_id' => $otherUser->id,
            ]);

        $this->assertDatabaseHas('providers', [
            'name' => 'Blida Barbershop',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('providers', [
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_invalid_timezone_is_rejected()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('provider.update'), [
                'name' => 'Blida Barbershop',
                'timezone' => 'Not/ATimezone',
            ]);

        $response->assertSessionHasErrors('timezone');
        $this->assertDatabaseMissing('providers', ['name' => 'Blida Barbershop']);
    }

    public function test_name_is_required()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('provider.update'), [
                'timezone' => 'Africa/Algiers',
            ]);

        $response->assertSessionHasErrors('name');
    }
}
