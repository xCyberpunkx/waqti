# WAQTI — ARCHITECTURAL DECISIONS

This is the deliberate decision record. A decision belongs here only when
it changes architecture, domain modeling, infrastructure, or long-term
product direction.

## 2026-09-02 — Backend framework bumped to Laravel 13

Changed: `FOUNDATION.md` §2 and `SOURCE_OF_TRUTH.md` §3 locked-stack entries,
from Laravel 12 to Laravel 13.

Reason: [fill in — e.g. Laravel 13 released and is now the current stable
version at project start, no reason to scaffold on 12]

Consequences: none yet — no code exists, so this is a documentation-only
change with no migration path required. Applies from the Phase 0
`laravel new waqti` scaffold onward.

No decisions recorded yet beyond the above — this log starts substantively
once Phase 0 begins.

Expected early decisions to record here once made:

- official Cloud API vs. any prototype-only unofficial library boundary
  (see `WHATSAPP_INTEGRATION.md` §1)
- bundled vs. pass-through message cost pricing (see `BILLING.md` §2)
- exact reminder offsets (24h/2h, or different) once tested against real
  no-show data
- when/if Phase 2 multi-provider tenancy actually gets triggered, and
  what boundary model is chosen (Velora-style `organization_id`, or
  something lighter given the smaller domain)

## 2026-09-03 — `clients` and `bookings` tables built during Step 2, not Step 4

Changed: nothing in `DATABASE_SCHEMA.md` — both tables were already
fully specified there under "Foundation tables" / "Scheduling". This
records *when* they were built, and the boundary drawn around that.

Reason: `TESTING.md` §3 requires "slot computation excludes
already-booked times" as a Step 2 (Availability) test. That's
impossible without a queryable `bookings` table, and `bookings.client_id`
is a required (non-nullable) FK per the schema, so `clients` had to
exist too.

Boundary drawn: only the schema/model layer was built early
(migrations, Eloquent models, a `Booking::scopeActive()` read helper).
No booking-creation logic, no client-onboarding/consent flow, no
overlap-prevention constraint, and no concurrency handling — those stay
Step 4, per the required implementation sequence in
`CLAUDE_HANDOFF.md`. `ComputeAvailableSlots` only reads from these
tables; nothing in Step 2 writes to `bookings` or `clients` outside of
test factories.

Consequences: Step 4 starts from an existing schema instead of
designing it fresh — the state-machine transitions and double-booking
constraint described in `DATABASE_SCHEMA.md` §3 still need to be
implemented and tested then.

## 2026-09-03 — At most one AvailabilityRule per (provider, weekday)

Changed: added a unique constraint on `(provider_id, weekday)` in the
`availability_rules` migration. `DATABASE_SCHEMA.md` doesn't specify
this either way.

Reason: keeps Step 2 CRUD simple (one form field set per weekday,
upsert semantics) and matches the ROADMAP.md Step 2 scope ("define
working hours" — singular per day). A provider needing a recurring
split shift (e.g. closed 12:00–14:00 every day) isn't a Phase 1
requirement.

Consequences: a one-off split shift on a specific date is expressible
via a `SlotException`. A *recurring* split shift is not expressible
without a schema change — revisit if a real provider needs one.

## 2026-09-04 — Replaced `prohibited_if:is_closed,true` with an explicit `withValidator()` check (first fix attempt — did not solve the actual bug)

Changed: `SlotExceptionStoreRequest`'s closed-vs-override-hours mutual
exclusion no longer uses `prohibited_if:is_closed,true`. It's now a
`withValidator()` closure using `$this->boolean('is_closed')`.

Reason: `test_posting_the_same_date_again_updates_rather_than_duplicates`
was failing, and `prohibited_if`'s behavior with a real boolean input
compared against the literal string `"true"` rule parameter depends on
Laravel's internal type-coercion rules — not something worth guessing
at without being able to execute the code. `Request::boolean()` has
simple, documented semantics instead.

Consequences: this change is fine and stayed in — but see the next
entry. It turned out **not** to be what was causing the test failure;
the test failed again after this fix, for a completely different
reason.

## 2026-09-04 — Actual root cause of the `SlotException` update bug: `updateOrCreate` on a `date`-cast column

Changed: `AvailabilityController::storeException()` no longer calls
`updateOrCreate(['date' => $string], $values)`. It now does an
explicit `whereDate('date', $string)->first()` lookup, then
`update()` or `create()`.

Reason: got a working PHP interpreter into this sandbox (Ubuntu
24.04's own repos only carry PHP 8.3, and this project requires 8.4 —
pulled `php8.4-cli` from Ubuntu 25.04's package pocket instead of
guessing further) and reproduced the failure directly instead of
reasoning about it. The real cause: `updateOrCreate`'s search array
`['date' => $request->validated('date')]` passes a bare string like
`'2026-09-11'` into a plain `where('date', ...)` clause. The `date`
cast's *stored* representation on SQLite carries a `00:00:00` time
component, so the string never matched an existing row. `first()`
came back empty, `updateOrCreate` attempted an **insert**, and that
hit the `(provider_id, date)` unique constraint — throwing a 500 that
Laravel's exception handler swallowed into a plain error response. The
original row was never touched, which is exactly why `is_closed`
stayed `true` in every version of the assertion failure, including
after the `prohibited_if` fix above (which was solving a real but
unrelated problem).

Consequences: `whereDate()` compares only the date portion and is
correct regardless of how the underlying driver stores a `date`-cast
value — this is the more general fix and should be preferred over
`updateOrCreate` for any future `date`-keyed lookup in this codebase,
not just this one. Full suite (87 tests) now passes, executed for real
in this sandbox — not reasoned from a stack trace.
