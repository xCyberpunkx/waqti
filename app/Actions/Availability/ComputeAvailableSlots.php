<?php

namespace App\Actions\Availability;

use App\Models\Booking;
use App\Models\Provider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Computes bookable slots for a Provider on a given date from their
 * AvailabilityRule + SlotException records, minus existing (active)
 * Bookings — never from a pre-generated slot table, per
 * DOMAIN_MODEL.md §3.
 *
 * Deliberately pure with respect to its inputs: given the same rule,
 * exception, and booking collections, `forDate()` always returns the
 * same result. Callers own fetching those from the database; this
 * class only reasons about time.
 */
class ComputeAvailableSlots
{
    /**
     * Compute available slots for one calendar date.
     *
     * Resolution order for that date:
     *
     * 1. A SlotException with `is_closed = true` → no slots, full stop,
     *    regardless of any AvailabilityRule.
     * 2. A SlotException with override hours (not closed) → those hours
     *    are used instead of the weekday's AvailabilityRule hours. Slot
     *    length still comes from the AvailabilityRule for that weekday
     *    (SlotException carries no slot-length field, per
     *    DATABASE_SCHEMA.md §3) — if no rule exists for that weekday at
     *    all, the override cannot be turned into slots, since there is
     *    no length to divide it into, so no slots are produced.
     * 3. No exception → the weekday's active AvailabilityRule (if any)
     *    is used as-is.
     * 4. No exception and no active rule for that weekday → no slots.
     *
     * Whatever hours are in effect, slots are then cut into
     * `slot_length_minutes` increments, and any slot overlapping an
     * active (pending/confirmed) Booking is excluded.
     *
     * @param  Collection<int, \App\Models\AvailabilityRule>  $rules  All of the provider's rules (any weekday).
     * @param  Collection<int, \App\Models\SlotException>  $exceptions  Exceptions covering the date (0 or 1 expected).
     * @param  Collection<int, Booking>  $bookings  Active bookings overlapping the date.
     * @return list<array{starts_at: Carbon, ends_at: Carbon}>
     */
    public function forDate(
        Provider $provider,
        Carbon $date,
        Collection $rules,
        Collection $exceptions,
        Collection $bookings,
    ): array {
        $exception = $exceptions->first(fn ($e) => $e->date->isSameDay($date));

        if ($exception && $exception->is_closed) {
            return [];
        }

        $rule = $rules->first(fn ($r) => (int) $r->weekday === $date->dayOfWeek && $r->is_active);

        [$startsAt, $endsAt] = $this->resolveHours($date, $rule, $exception);

        if ($startsAt === null || $endsAt === null || $rule === null) {
            return [];
        }

        $slots = $this->sliceIntoSlots($startsAt, $endsAt, (int) $rule->slot_length_minutes);

        return $this->excludeBooked($slots, $bookings);
    }

    /**
     * Compute available slots across an inclusive date range by calling
     * `forDate()` once per day.
     *
     * @param  Collection<int, \App\Models\AvailabilityRule>  $rules
     * @param  Collection<int, \App\Models\SlotException>  $exceptions
     * @param  Collection<int, Booking>  $bookings
     * @return array<string, list<array{starts_at: Carbon, ends_at: Carbon}>> Keyed by Y-m-d date string.
     */
    public function forRange(
        Provider $provider,
        Carbon $startDate,
        Carbon $endDate,
        Collection $rules,
        Collection $exceptions,
        Collection $bookings,
    ): array {
        $results = [];
        $cursor = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $dayBookings = $bookings->filter(fn (Booking $b) => $b->starts_at->isSameDay($cursor));

            $results[$cursor->toDateString()] = $this->forDate(
                $provider,
                $cursor,
                $rules,
                $exceptions,
                $dayBookings,
            );

            $cursor = $cursor->addDay();
        }

        return $results;
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null}
     */
    private function resolveHours(Carbon $date, $rule, $exception): array
    {
        if ($exception && $exception->override_starts_at && $exception->override_ends_at) {
            return [
                $this->combine($date, $exception->override_starts_at),
                $this->combine($date, $exception->override_ends_at),
            ];
        }

        if ($rule) {
            return [
                $this->combine($date, $rule->starts_at),
                $this->combine($date, $rule->ends_at),
            ];
        }

        return [null, null];
    }

    private function combine(Carbon $date, string $time): Carbon
    {
        return $date->copy()->setTimeFromTimeString($time);
    }

    /**
     * @return list<array{starts_at: Carbon, ends_at: Carbon}>
     */
    private function sliceIntoSlots(Carbon $startsAt, Carbon $endsAt, int $slotLengthMinutes): array
    {
        if ($slotLengthMinutes <= 0 || $startsAt->gte($endsAt)) {
            return [];
        }

        $slots = [];
        $cursor = $startsAt->copy();

        while ($cursor->copy()->addMinutes($slotLengthMinutes)->lte($endsAt)) {
            $slots[] = [
                'starts_at' => $cursor->copy(),
                'ends_at' => $cursor->copy()->addMinutes($slotLengthMinutes),
            ];
            $cursor = $cursor->addMinutes($slotLengthMinutes);
        }

        return $slots;
    }

    /**
     * @param  list<array{starts_at: Carbon, ends_at: Carbon}>  $slots
     * @param  Collection<int, Booking>  $bookings
     * @return list<array{starts_at: Carbon, ends_at: Carbon}>
     */
    private function excludeBooked(array $slots, Collection $bookings): array
    {
        $activeBookings = $bookings->filter(
            fn (Booking $b) => in_array($b->status, Booking::ACTIVE_STATUSES, true)
        );

        if ($activeBookings->isEmpty()) {
            return $slots;
        }

        return array_values(array_filter(
            $slots,
            fn (array $slot) => ! $activeBookings->contains(
                fn (Booking $b) => $slot['starts_at']->lt($b->ends_at) && $b->starts_at->lt($slot['ends_at'])
            )
        ));
    }
}
