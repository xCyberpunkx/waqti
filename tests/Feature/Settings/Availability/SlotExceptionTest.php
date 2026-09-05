<?php

namespace Tests\Feature\Settings\Availability;

use App\Models\Provider;
use App\Models\SlotException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_an_exception()
    {
        $response = $this->post(route('availability.exceptions.store'), []);

        $response->assertRedirect(route('login'));
    }

    public function test_a_closed_exception_can_be_created()
    {
        $user = User::factory()->create();
        $provider = Provider::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('availability.exceptions.store'), [
            'date' => now()->addWeek()->toDateString(),
            'is_closed' => true,
            'reason' => 'Public holiday',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('slot_exceptions', [
            'provider_id' => $provider->id,
            'is_closed' => true,
            'reason' => 'Public holiday',
        ]);
    }

    public function test_an_extra_hours_exception_can_be_created()
    {
        $user = User::factory()->create();
        $provider = Provider::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('availability.exceptions.store'), [
            'date' => now()->addWeek()->toDateString(),
            'is_closed' => false,
            'override_starts_at' => '10:00',
            'override_ends_at' => '14:00',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('slot_exceptions', [
            'provider_id' => $provider->id,
            'is_closed' => false,
        ]);
    }

    public function test_override_hours_are_rejected_when_the_date_is_marked_closed()
    {
        $user = User::factory()->create();
        Provider::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('availability.exceptions.store'), [
            'date' => now()->addWeek()->toDateString(),
            'is_closed' => true,
            'override_starts_at' => '10:00',
            'override_ends_at' => '14:00',
        ]);

        $response->assertSessionHasErrors(['override_starts_at', 'override_ends_at']);
    }

    public function test_a_past_date_is_rejected()
    {
        $user = User::factory()->create();
        Provider::factory()->for($user)->create();

        $response = $this->actingAs($user)->post(route('availability.exceptions.store'), [
            'date' => now()->subDay()->toDateString(),
            'is_closed' => true,
        ]);

        $response->assertSessionHasErrors('date');
    }

    public function test_posting_the_same_date_again_updates_rather_than_duplicates()
    {
        $user = User::factory()->create();
        $provider = Provider::factory()->for($user)->create();
        $date = now()->addWeek()->toDateString();

        SlotException::factory()->for($provider)->closed()->create(['date' => $date]);

        $this->actingAs($user)->post(route('availability.exceptions.store'), [
            'date' => $date,
            'is_closed' => false,
            'override_starts_at' => '10:00',
            'override_ends_at' => '12:00',
        ]);

        $this->assertSame(1, SlotException::where('provider_id', $provider->id)->count());
        $this->assertDatabaseHas('slot_exceptions', [
            'provider_id' => $provider->id,
            'is_closed' => false,
        ]);
    }

    public function test_a_provider_can_delete_their_own_exception()
    {
        $user = User::factory()->create();
        $provider = Provider::factory()->for($user)->create();
        $exception = SlotException::factory()->for($provider)->closed()->create();

        $response = $this->actingAs($user)->delete(route('availability.exceptions.destroy', $exception));

        $response->assertRedirect(route('availability.edit'));
        $this->assertDatabaseMissing('slot_exceptions', ['id' => $exception->id]);
    }

    public function test_a_provider_cannot_delete_another_providers_exception()
    {
        $user = User::factory()->create();
        Provider::factory()->for($user)->create();

        $otherProvider = Provider::factory()->create();
        $exception = SlotException::factory()->for($otherProvider)->closed()->create();

        $response = $this->actingAs($user)->delete(route('availability.exceptions.destroy', $exception));

        $response->assertForbidden();
        $this->assertDatabaseHas('slot_exceptions', ['id' => $exception->id]);
    }
}
