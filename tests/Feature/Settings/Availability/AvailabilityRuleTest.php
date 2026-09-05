<?php

namespace Tests\Feature\Settings\Availability;

use App\Models\AvailabilityRule;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_availability_settings()
    {
        $response = $this->get(route('availability.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_a_provider_is_redirected_to_create_one()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('availability.edit'));

        $response->assertRedirect(route('provider.edit'));
    }

    public function test_availability_page_shows_only_the_authenticated_users_own_rules()
    {
        $user = User::factory()->create();
        $provider = Provider::factory()->for($user)->create();
        AvailabilityRule::factory()->for($provider)->create(['weekday' => 1]);

        $otherProvider = Provider::factory()->create();
        AvailabilityRule::factory()->for($otherProvider)->create(['weekday' => 2]);

        $response = $this->actingAs($user)->get(route('availability.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('rules', 1)
            ->where('rules.0.weekday', 1)
        );
    }

    public function test_a_rule_can_be_created_for_a_weekday()
    {
        $user = User::factory()->create();
        $provider = Provider::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('availability.rules.upsert'), [
            'weekday' => 1,
            'starts_at' => '09:00',
            'ends_at' => '18:00',
            'slot_length_minutes' => 30,
            'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('availability.edit'));

        $this->assertDatabaseHas('availability_rules', [
            'provider_id' => $provider->id,
            'weekday' => 1,
            'slot_length_minutes' => 30,
        ]);
    }

    public function test_posting_the_same_weekday_again_updates_the_existing_rule_rather_than_duplicating()
    {
        $user = User::factory()->create();
        $provider = Provider::factory()->for($user)->create();
        AvailabilityRule::factory()->for($provider)->create([
            'weekday' => 1,
            'slot_length_minutes' => 30,
        ]);

        $this->actingAs($user)->post(route('availability.rules.upsert'), [
            'weekday' => 1,
            'starts_at' => '10:00',
            'ends_at' => '17:00',
            'slot_length_minutes' => 45,
            'is_active' => true,
        ]);

        $this->assertSame(1, AvailabilityRule::where('provider_id', $provider->id)->count());
        $this->assertDatabaseHas('availability_rules', [
            'provider_id' => $provider->id,
            'weekday' => 1,
            'slot_length_minutes' => 45,
        ]);
    }

    public function test_a_user_cannot_upsert_a_rule_without_a_provider()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('availability.rules.upsert'), [
            'weekday' => 1,
            'starts_at' => '09:00',
            'ends_at' => '18:00',
            'slot_length_minutes' => 30,
        ]);

        $response->assertForbidden();
    }

    public function test_end_time_must_be_after_start_time()
    {
        $user = User::factory()->create();
        Provider::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('availability.rules.upsert'), [
            'weekday' => 1,
            'starts_at' => '18:00',
            'ends_at' => '09:00',
            'slot_length_minutes' => 30,
        ]);

        $response->assertSessionHasErrors('ends_at');
    }

    public function test_weekday_must_be_between_0_and_6()
    {
        $user = User::factory()->create();
        Provider::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('availability.rules.upsert'), [
            'weekday' => 7,
            'starts_at' => '09:00',
            'ends_at' => '18:00',
            'slot_length_minutes' => 30,
        ]);

        $response->assertSessionHasErrors('weekday');
    }

    public function test_a_provider_can_delete_their_own_rule()
    {
        $user = User::factory()->create();
        $provider = Provider::factory()->for($user)->create();
        $rule = AvailabilityRule::factory()->for($provider)->create();

        $response = $this->actingAs($user)->delete(route('availability.rules.destroy', $rule));

        $response->assertRedirect(route('availability.edit'));
        $this->assertDatabaseMissing('availability_rules', ['id' => $rule->id]);
    }

    public function test_a_provider_cannot_delete_another_providers_rule()
    {
        $user = User::factory()->create();
        Provider::factory()->for($user)->create();

        $otherProvider = Provider::factory()->create();
        $rule = AvailabilityRule::factory()->for($otherProvider)->create();

        $response = $this->actingAs($user)->delete(route('availability.rules.destroy', $rule));

        $response->assertForbidden();
        $this->assertDatabaseHas('availability_rules', ['id' => $rule->id]);
    }

    public function test_upsert_ignores_a_spoofed_provider_id()
    {
        $user = User::factory()->create();
        $provider = Provider::factory()->for($user)->create();
        $otherProvider = Provider::factory()->create();

        $this->actingAs($user)->post(route('availability.rules.upsert'), [
            'weekday' => 1,
            'starts_at' => '09:00',
            'ends_at' => '18:00',
            'slot_length_minutes' => 30,
            'provider_id' => $otherProvider->id,
        ]);

        $this->assertDatabaseHas('availability_rules', [
            'provider_id' => $provider->id,
            'weekday' => 1,
        ]);
        $this->assertDatabaseMissing('availability_rules', [
            'provider_id' => $otherProvider->id,
            'weekday' => 1,
        ]);
    }
}
