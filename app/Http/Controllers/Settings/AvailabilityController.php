<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Availability\ComputeAvailableSlots;
use App\Http\Controllers\Controller;
use App\Http\Requests\Availability\AvailabilityRuleUpsertRequest;
use App\Http\Requests\Availability\AvailableSlotsRequest;
use App\Http\Requests\Availability\SlotExceptionStoreRequest;
use App\Models\AvailabilityRule;
use App\Models\SlotException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    /**
     * Show the working-hours settings page: one rule per weekday plus
     * upcoming exceptions.
     */
    public function edit(Request $request): Response|RedirectResponse
    {
        $provider = $request->user()->provider;

        // Availability is meaningless without a provider record —
        // send the user to create one first rather than rendering an
        // empty/broken page.
        if ($provider === null) {
            return to_route('provider.edit');
        }

        return Inertia::render('settings/availability', [
            'rules' => $provider->availabilityRules()
                ->orderBy('weekday')
                ->get(['id', 'weekday', 'starts_at', 'ends_at', 'slot_length_minutes', 'is_active']),
            'exceptions' => $provider->slotExceptions()
                ->where('date', '>=', Carbon::today())
                ->orderBy('date')
                ->get(['id', 'date', 'is_closed', 'override_starts_at', 'override_ends_at', 'reason']),
        ]);
    }

    /**
     * Create or update the rule for the submitted weekday. `provider_id`
     * is always relation-derived, never request input (SECURITY.md §7).
     */
    public function upsertRule(AvailabilityRuleUpsertRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $request->user()->provider->availabilityRules()->updateOrCreate(
            ['weekday' => $validated['weekday']],
            $validated,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Working hours saved.')]);

        return to_route('availability.edit');
    }

    /**
     * Delete a weekday's rule (the provider is closed that weekday by
     * default once no rule exists).
     */
    public function destroyRule(Request $request, AvailabilityRule $rule): RedirectResponse
    {
        $request->user()->can('delete', $rule) || abort(403);

        $rule->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Working hours removed.')]);

        return to_route('availability.edit');
    }

    /**
     * Create a one-off closure or extra-hours exception for a specific
     * date.
     *
     * Deliberately does not use `updateOrCreate(['date' => ...], ...)`
     * — a plain `where('date', $string)` doesn't reliably match the
     * `date`-cast column's stored representation (confirmed: on
     * SQLite it's serialized with a `00:00:00` time component, so a
     * bare date string never matches and a duplicate insert is
     * attempted, tripping the unique constraint). `whereDate()`
     * compares only the date portion and is correct regardless of how
     * the underlying driver stores it.
     */
    public function storeException(SlotExceptionStoreRequest $request): RedirectResponse
    {
        $provider = $request->user()->provider;

        $exception = $provider->slotExceptions()
            ->whereDate('date', $request->validated('date'))
            ->first();

        if ($exception) {
            $exception->update($request->validated());
        } else {
            $provider->slotExceptions()->create($request->validated());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Exception saved.')]);

        return to_route('availability.edit');
    }

    /**
     * Remove an exception, reverting that date back to the normal
     * weekday rule.
     */
    public function destroyException(Request $request, SlotException $exception): RedirectResponse
    {
        $request->user()->can('delete', $exception) || abort(403);

        $exception->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Exception removed.')]);

        return to_route('availability.edit');
    }

    /**
     * Computed available slots for the authenticated provider over a
     * date range — rules + exceptions minus active bookings, per
     * App\Actions\Availability\ComputeAvailableSlots. Used to preview
     * availability now; the WhatsApp booking flow (Step 4) will call
     * the same computation.
     */
    public function slots(AvailableSlotsRequest $request, ComputeAvailableSlots $computeAvailableSlots): JsonResponse
    {
        $provider = $request->user()->provider;

        $from = Carbon::parse($request->validated('from'))->startOfDay();
        $to = Carbon::parse($request->validated('to'))->startOfDay();

        $rules = $provider->availabilityRules()->get();
        $exceptions = $provider->slotExceptions()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get();
        $bookings = $provider->bookings()->active()
            ->whereBetween('starts_at', [$from, $to->copy()->endOfDay()])
            ->get();

        $slots = $computeAvailableSlots->forRange($provider, $from, $to, $rules, $exceptions, $bookings);

        return response()->json([
            'slots' => collect($slots)->map(
                fn (array $daySlots) => collect($daySlots)->map(fn (array $slot) => [
                    'starts_at' => $slot['starts_at']->toIso8601String(),
                    'ends_at' => $slot['ends_at']->toIso8601String(),
                ])
            ),
        ]);
    }
}
