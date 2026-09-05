<?php

namespace Tests\Feature\Settings\Availability;

use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AvailableSlotsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_fetch_available_slots()
    {
        $response = $this->getJson(route('availability.slots', [
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertUnauthorized();
    }

    public function test_a_user_without_a_provider_cannot_fetch_slots()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('availability.slots', [
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertForbidden();
    }

    public function test_slots_reflect_the_authenticated_users_own_rules_and_bookings()
    {
        $user = User::factory()->create();
        $provider = Provider::factory()->for($user)->create();

        // Find the next Monday so the test is deterministic regardless
        // of "today".
        $monday = Carbon::now()->next(Carbon::MONDAY)->startOfDay();

        AvailabilityRule::factory()->for($provider)->create([
            'weekday' => 1,
            'starts_at' => '09:00',
            'ends_at' => '10:00',
            'slot_length_minutes' => 30,
        ]);

        $client = Client::factory()->for($provider)->create();
        Booking::factory()->for($provider)->for($client)->create([
            'starts_at' => $monday->copy()->setTime(9, 0),
            'ends_at' => $monday->copy()->setTime(9, 30),
            'status' => 'confirmed',
        ]);

        // Another provider's data must never leak in.
        $otherProvider = Provider::factory()->create();
        AvailabilityRule::factory()->for($otherProvider)->create([
            'weekday' => 1,
            'starts_at' => '00:00',
            'ends_at' => '23:00',
            'slot_length_minutes' => 60,
        ]);

        $response = $this->actingAs($user)->getJson(route('availability.slots', [
            'from' => $monday->toDateString(),
            'to' => $monday->toDateString(),
        ]));

        $response->assertOk();

        $daySlots = $response->json('slots.'.$monday->toDateString());

        $this->assertCount(1, $daySlots);
        $this->assertStringContainsString('T09:30:00', $daySlots[0]['starts_at']);
    }
}
