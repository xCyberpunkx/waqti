<?php

namespace Tests\Unit\Availability;

use App\Actions\Availability\ComputeAvailableSlots;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\Provider;
use App\Models\SlotException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Deliberately does not use RefreshDatabase — models here are
 * constructed in memory (`new Model([...])`, never saved), so this
 * exercises pure business logic per TESTING.md §2, not the database.
 */
class ComputeAvailableSlotsTest extends TestCase
{
    private ComputeAvailableSlots $compute;

    private Provider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compute = new ComputeAvailableSlots;
        $this->provider = new Provider(['name' => 'Test Provider']);
    }

    private function rule(int $weekday, string $starts, string $ends, int $slotLength, bool $active = true): AvailabilityRule
    {
        return new AvailabilityRule([
            'weekday' => $weekday,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'slot_length_minutes' => $slotLength,
            'is_active' => $active,
        ]);
    }

    private function exception(Carbon $date, bool $closed, ?string $overrideStarts = null, ?string $overrideEnds = null): SlotException
    {
        return new SlotException([
            'date' => $date->toDateString(),
            'is_closed' => $closed,
            'override_starts_at' => $overrideStarts,
            'override_ends_at' => $overrideEnds,
        ]);
    }

    private function booking(Carbon $startsAt, Carbon $endsAt, string $status = 'confirmed'): Booking
    {
        return new Booking([
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => $status,
            'source' => 'manual',
        ]);
    }

    public function test_slots_are_generated_from_a_matching_active_rule()
    {
        // A known Monday.
        $monday = Carbon::parse('2026-09-07');
        $this->assertSame(1, $monday->dayOfWeek);

        $rules = new Collection([$this->rule(1, '09:00', '10:00', 30)]);

        $slots = $this->compute->forDate($this->provider, $monday, $rules, new Collection, new Collection);

        $this->assertCount(2, $slots);
        $this->assertSame('09:00', $slots[0]['starts_at']->format('H:i'));
        $this->assertSame('09:30', $slots[0]['ends_at']->format('H:i'));
        $this->assertSame('09:30', $slots[1]['starts_at']->format('H:i'));
        $this->assertSame('10:00', $slots[1]['ends_at']->format('H:i'));
    }

    public function test_no_slots_when_no_rule_exists_for_that_weekday()
    {
        $sunday = Carbon::parse('2026-09-06');
        $rules = new Collection([$this->rule(1, '09:00', '10:00', 30)]); // Monday only

        $slots = $this->compute->forDate($this->provider, $sunday, $rules, new Collection, new Collection);

        $this->assertSame([], $slots);
    }

    public function test_inactive_rule_produces_no_slots()
    {
        $monday = Carbon::parse('2026-09-07');
        $rules = new Collection([$this->rule(1, '09:00', '10:00', 30, active: false)]);

        $slots = $this->compute->forDate($this->provider, $monday, $rules, new Collection, new Collection);

        $this->assertSame([], $slots);
    }

    public function test_a_closed_exception_overrides_an_active_rule_entirely()
    {
        $monday = Carbon::parse('2026-09-07');
        $rules = new Collection([$this->rule(1, '09:00', '18:00', 30)]);
        $exceptions = new Collection([$this->exception($monday, closed: true)]);

        $slots = $this->compute->forDate($this->provider, $monday, $rules, $exceptions, new Collection);

        $this->assertSame([], $slots);
    }

    public function test_an_extra_hours_exception_replaces_the_rules_hours_but_keeps_its_slot_length()
    {
        $sunday = Carbon::parse('2026-09-06'); // normally closed, no rule
        $rules = new Collection([$this->rule(0, '09:00', '13:00', 60)]); // Sunday rule exists but with different hours
        $exceptions = new Collection([
            $this->exception($sunday, closed: false, overrideStarts: '14:00', overrideEnds: '16:00'),
        ]);

        $slots = $this->compute->forDate($this->provider, $sunday, $rules, $exceptions, new Collection);

        $this->assertCount(2, $slots);
        $this->assertSame('14:00', $slots[0]['starts_at']->format('H:i'));
        $this->assertSame('15:00', $slots[0]['ends_at']->format('H:i'));
    }

    public function test_extra_hours_exception_produces_no_slots_when_no_rule_exists_for_that_weekday_to_source_slot_length()
    {
        $sunday = Carbon::parse('2026-09-06');
        $exceptions = new Collection([
            $this->exception($sunday, closed: false, overrideStarts: '14:00', overrideEnds: '16:00'),
        ]);

        $slots = $this->compute->forDate($this->provider, $sunday, new Collection, $exceptions, new Collection);

        $this->assertSame([], $slots);
    }

    public function test_slot_computation_excludes_already_booked_times()
    {
        $monday = Carbon::parse('2026-09-07');
        $rules = new Collection([$this->rule(1, '09:00', '10:30', 30)]);

        // Books the 09:30–10:00 slot.
        $bookings = new Collection([
            $this->booking(
                Carbon::parse('2026-09-07 09:30'),
                Carbon::parse('2026-09-07 10:00'),
            ),
        ]);

        $slots = $this->compute->forDate($this->provider, $monday, $rules, new Collection, $bookings);

        $this->assertCount(2, $slots);
        $starts = array_map(fn ($s) => $s['starts_at']->format('H:i'), $slots);
        $this->assertSame(['09:00', '10:00'], $starts);
    }

    public function test_cancelled_bookings_do_not_block_a_slot()
    {
        $monday = Carbon::parse('2026-09-07');
        $rules = new Collection([$this->rule(1, '09:00', '10:00', 30)]);

        $bookings = new Collection([
            $this->booking(
                Carbon::parse('2026-09-07 09:00'),
                Carbon::parse('2026-09-07 09:30'),
                status: 'cancelled',
            ),
        ]);

        $slots = $this->compute->forDate($this->provider, $monday, $rules, new Collection, $bookings);

        $this->assertCount(2, $slots);
    }

    public function test_pending_bookings_block_a_slot_the_same_as_confirmed()
    {
        $monday = Carbon::parse('2026-09-07');
        $rules = new Collection([$this->rule(1, '09:00', '10:00', 30)]);

        $bookings = new Collection([
            $this->booking(
                Carbon::parse('2026-09-07 09:00'),
                Carbon::parse('2026-09-07 09:30'),
                status: 'pending',
            ),
        ]);

        $slots = $this->compute->forDate($this->provider, $monday, $rules, new Collection, $bookings);

        $this->assertCount(1, $slots);
        $this->assertSame('09:30', $slots[0]['starts_at']->format('H:i'));
    }

    public function test_a_trailing_partial_slot_that_does_not_fit_the_slot_length_is_dropped()
    {
        $monday = Carbon::parse('2026-09-07');
        // 09:00-10:00 with 40-minute slots -> only one full slot fits (09:00-09:40).
        $rules = new Collection([$this->rule(1, '09:00', '10:00', 40)]);

        $slots = $this->compute->forDate($this->provider, $monday, $rules, new Collection, new Collection);

        $this->assertCount(1, $slots);
        $this->assertSame('09:00', $slots[0]['starts_at']->format('H:i'));
        $this->assertSame('09:40', $slots[0]['ends_at']->format('H:i'));
    }

    public function test_for_range_computes_each_day_independently()
    {
        $rules = new Collection([
            $this->rule(1, '09:00', '10:00', 30), // Monday
        ]);

        $results = $this->compute->forRange(
            $this->provider,
            Carbon::parse('2026-09-06'), // Sunday
            Carbon::parse('2026-09-08'), // Tuesday
            $rules,
            new Collection,
            new Collection,
        );

        $this->assertSame([], $results['2026-09-06']);
        $this->assertCount(2, $results['2026-09-07']);
        $this->assertSame([], $results['2026-09-08']);
    }
}
