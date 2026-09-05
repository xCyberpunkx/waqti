# WAQTI — PROGRESS

This document describes reality, not aspirations.

Update at the end of each meaningful working session.

### Date
2026-09-02

### What I changed
Completed Phase 0: repo created and flattened, Laravel 13 + Inertia +
React/TS scaffold installed, Docker Compose (Postgres + Redis) running,
Pest installed, APP_KEY generated.

### What I tested
Full test suite via `php artisan test`.

### What passed
All tests green (38 previously failing on MissingAppKeyException, fixed
via `php artisan key:generate`).

### What failed
Nothing currently — earlier failures were APP_KEY missing, resolved.

### Database changes
None yet — no migrations beyond Laravel's defaults have been run against
domain tables.

### Security impact
None yet — no domain logic exists.

### Decisions made
Laravel bumped to 13 (recorded in DECISIONS.md).

### Next exact task
[Meta Developer / WhatsApp Business Account provisioning + sandbox test
message, if not done — OR Phase 1 Step 1 (Provider + Auth) if Meta setup
is already in progress separately]

### Notes / blockers
None.

---

### Date
2026-09-03

### What I changed
No code changes. Verification-only session: read all docs per
`CLAUDE_HANDOFF.md` priority order, then inspected the actual repository
to confirm Phase 0 status before writing any Phase 1 code.

### What I tested
Static inspection only — `php` is not available in this environment, so
`php artisan test` could not be re-run to confirm the prior session's
"all green" claim. Confirmed instead by reading migrations, models,
routes, and `app/` file listing directly.

### What passed / confirmed
- Repo matches PROGRESS.md's Phase 0 claim: Laravel `^13.17`, Inertia +
  React/TS, Docker Compose with `postgres:16` + `redis:7-alpine`, Pest
  test files present (`tests/Feature/Auth/*`, `tests/Feature/Dashboard
  Test.php`, `tests/Unit/ExampleTest.php`, etc.).
- Auth scaffolding exists via Laravel Fortify: login, registration,
  password reset, 2FA, passkeys (`app/Actions/Fortify/*`,
  `app/Http/Controllers/Settings/SecurityController.php`, migrations for
  `users`, `passkeys`, two-factor columns).
- No domain migrations exist beyond Laravel/Fortify defaults — no
  `providers`, `clients`, `availability_rules`, `slot_exceptions`,
  `bookings`, `inbound_messages`, `outbound_messages`,
  `conversation_states`, or `reminder_logs` tables. Confirms
  `DATABASE_SCHEMA.md` is target-only, nothing built yet.
- No WhatsApp integration code anywhere in `app/` (`grep -ril whatsapp`
  returned nothing outside docs).
- `git log` confirms history is scaffold-only (initial commit, repo
  flatten, README, npm build fix) — no domain-feature commits.

### What failed
Nothing failed; this was a read-only verification pass.

### Discrepancy found (repo vs. docs)
`SOURCE_OF_TRUTH.md` §3 states Redis is required for queue + reminder
scheduling "from day one, not deferred." `docker-compose.yml` does run a
Redis service, but `.env` and `.env.example` both set
`QUEUE_CONNECTION=database`, not `redis`. Redis is running but not
actually wired up as the queue driver yet. Not a blocker for Step 1
(Provider + Auth), but should be corrected before Step 3 (webhook
inbound pipeline / reminder queueing) is built, per the Source of Truth
commitment.

### Database changes
None.

### Security impact
None — no domain logic touched.

### Decisions made
None. Updated `CLAUDE_HANDOFF.md`'s "Current task" section, which had
gone stale (still said "Phase 0... nothing has been built yet" despite
Phase 0 being complete) — this is the exact staleness failure mode the
handoff doc itself warns against repeating from Velora.

### Next exact task
Phase 1 Step 1 — Provider + Auth: create `providers` table/model (per
`DATABASE_SCHEMA.md` §2), encrypted `whatsapp_access_token` storage, and
link it to the existing Fortify-based dashboard auth. Blocked on
confirming with the user whether Meta Developer / WhatsApp Business
Account provisioning has happened yet (repo can't tell either way), and
on a decision about the Redis queue-driver discrepancy above.

### Notes / blockers
Need user confirmation on: (1) Meta/WhatsApp Business Account
provisioning status, (2) whether to switch `QUEUE_CONNECTION` to `redis`
now or defer to Step 3.

---

### Date
2026-09-03 (same day, continued)

### What I changed
Phase 1 Step 1 — Provider + Auth. User confirmed: Meta/WhatsApp Business
Account not provisioned yet (external task, still to do); Redis
queue-driver gap deferred to Step 3 as planned.

Built:
- `providers` migration (per `DATABASE_SCHEMA.md` §2): `user_id` FK
  (unique — one provider per dashboard user in Phase 1), `name`,
  `business_category`, `timezone` (defaults `Africa/Algiers`),
  `whatsapp_phone_number_id`, `whatsapp_business_account_id`,
  `whatsapp_access_token`.
- `App\Models\Provider` — `whatsapp_access_token` uses the `encrypted`
  cast (SECURITY.md §3); only `name`/`business_category`/`timezone` are
  fillable, WhatsApp fields are never mass-assignable.
- `User::provider()` hasOne relation.
- `App\Policies\ProviderPolicy` — `view`/`update` scoped to
  `user->id === provider->user_id`.
- `ProviderProfileUpdateRequest` and
  `ProviderWhatsappCredentialsUpdateRequest` — both authorize via the
  policy (or allow creation when no provider exists yet for that user);
  access token is required on first save, `sometimes` afterward so
  editing the phone/business-account IDs doesn't force re-entering the
  token.
- `Settings\ProviderController` — `edit`, `updateProfile`,
  `updateWhatsappCredentials`. Uses `$user->provider()->updateOrCreate`
  so `user_id` is always relation-derived, never request input (
  SECURITY.md §7). `edit()` only ever returns
  `has_whatsapp_access_token: bool` to the frontend, never the token.
- Routes: `settings/provider` (GET/PATCH), `settings/provider/whatsapp`
  (PATCH), added to the existing `auth` middleware group in
  `routes/settings.php`.
- `resources/js/pages/settings/provider.tsx` — business profile form +
  WhatsApp credentials form, following the existing profile.tsx/Fortify
  page conventions (Inertia `<Form>` + generated Wayfinder actions).
  Added "Business" to the settings sidebar nav
  (`layouts/settings/layout.tsx`).
- `ProviderFactory` (with a `withWhatsappCredentials()` state) for
  tests.

### What I tested
Wrote (not executed — `php` is unavailable in this environment; needs
running locally):
- `tests/Feature/Settings/ProviderProfileUpdateTest.php` — guest
  redirected; page renders with `provider: null` before first save;
  page only ever shows the authenticated user's own provider; profile
  created on first save; profile updatable; a spoofed `user_id` in the
  request body is ignored; invalid timezone rejected; name required.
- `tests/Feature/Settings/ProviderWhatsappCredentialsTest.php` — guest
  blocked; credentials stored on first save; token required first time,
  not required on subsequent edits (and not overwritten when omitted);
  token is not stored in plaintext in the `providers` table (raw DB
  read via `DB::table`, asserted the ciphertext doesn't contain the
  plaintext token); `edit()` page payload never includes the raw token,
  only the boolean flag; spoofed `user_id` on the credentials form is
  ignored.

### What passed
Unverified locally — could not run `php artisan test` in this
environment (no PHP binary available). **Run the full suite locally
before trusting this as done** — do not treat this as tested per
`TESTING.md`'s definition of done until that happens.

### What failed
N/A — not yet run.

### Database changes
Added `providers` table (see migration above). No existing tables
altered.

### Security impact
- New mutation surface (`provider.update`,
  `provider.whatsapp.update`) — both authorized server-side via
  `ProviderPolicy`/request `authorize()`, both covered by an
  authorization test per SECURITY.md §11.
- `whatsapp_access_token` encrypted at rest via Eloquent cast; never
  returned to the frontend; mass-assignment-protected (SECURITY.md §3,
  §7).
- No rate limiting added yet to these routes — SECURITY.md §8 only
  explicitly calls out login/password-reset/webhook; revisit if this
  form turns out to need it too.

### Decisions made
None requiring a `DECISIONS.md` entry — straightforward Step 1
implementation of what `DATABASE_SCHEMA.md`/`DOMAIN_MODEL.md` already
specified. No architecture deviation.

### Next exact task
1. **Run the test suite locally** (`php artisan test`) — this session's
   tests are unverified. Fix anything that fails before moving on.
2. Provision the Meta Developer app / WhatsApp Business Account
   (external, non-repo task) — needed before the credentials form can
   be exercised against a real number, and before Step 3's webhook work
   can be tested end-to-end.
3. Once both of the above are done: Phase 1 Step 2 — Availability
   (`availability_rules` + `slot_exceptions` tables, computed
   available-slots endpoint).

### Notes / blockers
Cannot run PHP/Composer/npm in this environment — all backend/frontend
code here is unexecuted. Treat as a draft implementation pending a real
local test run, not as verified-working.

---

### Date
2026-09-03 (same day, third session — first real local test run)

### What I changed
User ran `php artisan migrate` (clean) and `php artisan test` locally.
6 of 54 tests failed. Root-caused and fixed:

**Bug found:** `Provider`'s `#[Fillable(...)]` attribute only listed
`name`/`business_category`/`timezone`. `ProviderController` sets
WhatsApp fields via `updateOrCreate()`, which calls `fill()`
internally — Laravel's default (non-strict) mass-assignment guard
silently drops any key not in `#[Fillable]`, so
`whatsapp_phone_number_id`/`whatsapp_business_account_id`/
`whatsapp_access_token` were never actually being written, no
exception thrown. The comment in the original code claimed this was
intentional protection; it was actually a bug — the real
mass-assignment boundary (SECURITY.md §7) is the FormRequest classes
only validating/returning fields relevant to their own form, not the
model's fillable list. Fixed by adding the three WhatsApp fields to
`Provider`'s `#[Fillable(...)]`; corrected the misleading comments in
`Provider.php` and `ProviderProfileUpdateRequest.php` that described
the old (broken) reasoning.

**Not a bug — expected, pending a build:** 4 of the 6 failures were
`ViteException: Unable to locate file in Vite manifest:
resources/js/pages/settings/provider.tsx`. This is normal — the Vite
manifest used by `php artisan test` is only refreshed by
`npm run build` (or `npm run dev` for local browsing), and that hadn't
been run yet after adding `provider.tsx`. No code change needed; user
needs to run the frontend build.

### What I tested
Nothing further executed by me (still no PHP/npm available here). The
fix targets exactly the 2 non-Vite failures
(`whatsapp_credentials_can_be_stored_on_first_save`,
`access_token_is_not_required_to_update_other_fields_once_set`) and, by
the same root cause, should also fix
`whatsapp_access_token_is_encrypted_at_rest` (it never got a value to
encrypt). The 3 Vite-manifest failures aren't a code problem — they
should pass once the manifest includes `provider.tsx`.

### What passed
48/54 on the user's first real run (see above for the 6 failures and
their causes/fixes).

### What failed
See "What I changed" — 2 caused by the fillable bug (now fixed, needs
re-run to confirm), 1 more from the same root cause
(`encrypted_at_rest` test), 3 from a stale Vite manifest (needs
`npm run build`, not a code fix).

### Database changes
None this session (fix is model-layer only, no new migration).

### Security impact
The bug being fixed was a silent *failure to write* data, not an
exposure — no credential was ever stored insecurely; it just wasn't
stored at all. No new exposure introduced by the fix: `user_id` is
still never in any `#[Fillable]` list and still can't be set through
either FormRequest, and the two FormRequests are still the actual
boundary controlling which fields each form can touch.

### Decisions made
None requiring `DECISIONS.md` — bug fix, not an architecture change.

### Next exact task
1. Re-copy `app/Models/Provider.php` and
   `app/Http/Requests/Settings/ProviderProfileUpdateRequest.php`
   (updated this session) into the repo.
2. Run `npm run build` (or `npm run dev`) to refresh the Vite manifest
   so `provider.tsx` resolves.
3. Re-run `php artisan test` — expect 54/54. If anything's still red,
   send the output back.
4. Once green: manually click through Settings → Business in the
   browser, then commit.
5. After that: Meta Developer/WhatsApp Business Account provisioning
   (still not done, per earlier session), then Phase 1 Step 2 —
   Availability.

### Notes / blockers
Same as before — no PHP/npm in my environment, so this fix is
reasoned from the stack trace/assertion diffs, not re-executed. Confirm
with a real test run before trusting it.

---

### Date
2026-09-03 (same day, fourth session)

### What I changed
User confirmed Meta Developer / WhatsApp Business Account provisioning
is done: test number provisioned, credentials saved in Settings →
Business, a real test message sent and received. That closes the
Phase 0 exit criterion that was still outstanding, and Phase 1 Step 1
is now fully closed end-to-end (not just code-complete).

Started Phase 1 Step 2 — Availability. Scope decision made and recorded
in `DECISIONS.md`: `TESTING.md` §3 requires slot computation to exclude
already-booked times, which needs a queryable `bookings` table, which
in turn needs `clients` (required FK, per `DATABASE_SCHEMA.md` §3).
Built both now as schema only — no booking-creation logic, no
client-onboarding flow, no overlap constraint. Those stay Step 4 per
`CLAUDE_HANDOFF.md`'s required sequence.

Built:
- Migrations: `clients`, `availability_rules` (unique per
  provider+weekday — see `DECISIONS.md`), `slot_exceptions` (no
  `updated_at`, per schema), `bookings` (schema only).
- Models: `Client`, `AvailabilityRule`, `SlotException`, `Booking`
  (with `Booking::scopeActive()` for pending/confirmed — the statuses
  that occupy a slot). `Provider` gained
  `availabilityRules()`/`slotExceptions()`/`clients()`/`bookings()`
  relations.
- `App\Actions\Availability\ComputeAvailableSlots` — pure logic: for a
  date, resolves the effective hours (closed exception > extra-hours
  exception > weekday rule > nothing), slices into
  `slot_length_minutes` increments, excludes any slot overlapping an
  active booking. `forRange()` calls it per-day across a range.
- `AvailabilityRulePolicy`, `SlotExceptionPolicy` — same
  ownership-check pattern as `ProviderPolicy`.
- `AvailabilityRuleUpsertRequest`, `SlotExceptionStoreRequest`,
  `AvailableSlotsRequest` — all authorize via "user has a provider" +
  ownership, none accept `provider_id` from the request.
- `Settings\AvailabilityController` — `edit` (renders the page, or
  redirects to provider setup if no provider exists yet),
  `upsertRule`/`destroyRule`, `storeException`/`destroyException`, and
  `slots()` (JSON endpoint calling `ComputeAvailableSlots` — this is
  what Step 4's booking flow will call later, not something new being
  introduced for its own sake).
- Routes under `settings/availability*`, added to the existing `auth`
  middleware group.
- `resources/js/pages/settings/availability.tsx` — one row per weekday
  (checkbox + start/end/slot-length + save, each its own small form),
  an exceptions list with delete, and a form to add a new exception.
  "Availability" added to the settings sidebar nav.
- Factories: `ClientFactory`, `AvailabilityRuleFactory`,
  `SlotExceptionFactory`, `BookingFactory`.

### What I tested
Wrote (not executed — `php` is still unavailable in this environment):
- `tests/Unit/Availability/ComputeAvailableSlotsTest.php` — genuinely
  DB-free (models constructed with `new Model([...])`, never saved):
  slots generated from a matching active rule; no slots when no rule
  exists for that weekday; inactive rule produces no slots; a closed
  exception overrides an active rule entirely; an extra-hours exception
  replaces the rule's hours but keeps its slot length; an extra-hours
  exception with no underlying rule produces no slots (documented
  interpretation gap in the schema — see the Action's docblock);
  already-booked times are excluded; cancelled bookings don't block a
  slot; pending blocks the same as confirmed; a trailing partial slot
  that doesn't fit the slot length is dropped; `forRange()` computes
  each day independently.
- `tests/Feature/Settings/Availability/AvailabilityRuleTest.php` —
  guest blocked; user without a provider redirected off the page (and
  gets 403 on upsert); page shows only the authenticated user's own
  rules; rule created; posting the same weekday again updates instead
  of duplicating; end-time-after-start-time and weekday-range
  validation; owner can delete their own rule, cannot delete another
  provider's rule; a spoofed `provider_id` is ignored.
- `tests/Feature/Settings/Availability/SlotExceptionTest.php` — guest
  blocked; closed exception created; extra-hours exception created;
  override hours rejected when `is_closed` is true; past dates
  rejected; posting the same date again updates instead of
  duplicating; owner can delete their own exception, cannot delete
  another provider's.
- `tests/Feature/Settings/Availability/AvailableSlotsEndpointTest.php`
  — guest unauthorized; user without a provider forbidden; slots
  reflect only the authenticated user's own rules/bookings (a second
  provider's wide-open rule on the same weekday must not leak in).

### What passed
Unverified locally — same caveat as the last two sessions. **Run
`php artisan migrate` and `php artisan test` before trusting this as
done**, per `TESTING.md`'s definition of done.

### What failed
N/A — not yet run.

### Database changes
Added `clients`, `availability_rules`, `slot_exceptions`, `bookings`
(see migrations above and `DECISIONS.md` for the `bookings`/`clients`
timing call and the one-rule-per-weekday constraint).

### Security impact
- Four new mutation surfaces (`availability.rules.upsert`,
  `availability.rules.destroy`, `availability.exceptions.store`,
  `availability.exceptions.destroy`) plus one new read surface
  (`availability.slots`) — all authorized server-side, all covered by
  an authorization test per SECURITY.md §11.
- `provider_id` is never accepted from any request; always
  relation-derived from the authenticated user's own provider, same
  pattern as `ProviderController`.
- No rate limiting added — these are authenticated dashboard routes,
  same category as the existing provider-settings routes which also
  don't have it yet (SECURITY.md §8 calls out login/password-reset/
  webhook specifically).

### Decisions made
Recorded in `DECISIONS.md`: (1) building `clients`/`bookings` schema
during Step 2 instead of Step 4, with the boundary of what was and
wasn't built; (2) one `AvailabilityRule` per (provider, weekday).

### Next exact task
1. **Run `php artisan migrate` and `php artisan test` locally** — this
   entire session is unverified. In particular, re-check
   `test_a_trailing_partial_slot_that_does_not_fit_the_slot_length_is_dropped`
   and the timezone-sensitive parts of `AvailableSlotsEndpointTest`
   against real `date`/`datetime` column round-tripping through
   Postgres.
2. Run `npm run build` (or `npm run dev`) so the Vite manifest picks up
   `availability.tsx` and Wayfinder generates
   `resources/js/routes/availability/*` and
   `resources/js/actions/.../AvailabilityController.ts` (not committed
   by me — these are generated files, per the existing `provider.tsx`
   precedent).
3. Once green: manually click through Settings → Availability in the
   browser (set a weekday's hours, add/remove an exception), then
   commit.
4. After that: Phase 1 Step 3 — WhatsApp Inbound Pipeline (webhook
   endpoint, signature verification, queue, idempotency by
   `whatsapp_message_id`, conversation-state skeleton) — per the
   required sequence, this comes before the booking flow (Step 4) that
   will actually create rows in `bookings`.

### Notes / blockers
Same recurring caveat — no PHP/Composer/npm available in this
environment. Everything above is reasoned from the code, not executed.
Treat as a draft pending a real local run.

---

### Date
2026-09-04

### What I changed
User ran `php artisan test` locally: 86 passed, 1 failed —
`SlotExceptionTest::test_posting_the_same_date_again_updates_rather_than_duplicates`.
The existing row's `is_closed` stayed `true` after a request meant to
flip it to `false` with override hours, meaning the request never
reached the controller (validation failed silently — the test doesn't
assert `assertSessionHasNoErrors()`, so nothing surfaced it directly).

Root cause: `SlotExceptionStoreRequest` used
`prohibited_if:is_closed,true` to keep override hours and "closed"
mutually exclusive. That rule's outcome depends on how Laravel
type-coerces a real boolean input against the literal string `"true"`
parameter — not something I could verify without a working PHP
interpreter (tried installing one in this sandbox; `noble-updates` is
currently 404ing on `php8.3-cli` and friends). Rather than guess a
second time, replaced it with an explicit `withValidator()` closure
using `$this->boolean('is_closed')`. See `DECISIONS.md` for the full
writeup.

### What I tested
No new tests added — the existing
`SlotExceptionTest::test_override_hours_are_rejected_when_the_date_is_marked_closed`
already covers the behavior this fix needs to preserve (override hours
rejected when closed); it was passing before and should still pass,
since the new closure produces the same two field errors
(`override_starts_at`, `override_ends_at`).

### What passed / What failed
That fix was wrong. User re-ran the tests: same failure, byte-for-byte
identical assertion. So this session got a real PHP interpreter
working in the sandbox instead of continuing to guess (Ubuntu 24.04's
own repos only have PHP 8.3; this project needs 8.4 — installed
`php8.4-cli` from Ubuntu 25.04's package pocket) and reproduced the
failure directly with `withoutExceptionHandling()`.

**Actual root cause**: `AvailabilityController::storeException()`'s
`updateOrCreate(['date' => $string], ...)` doesn't reliably match an
existing row on a `date`-cast column — the stored value on SQLite
carries a `00:00:00` time component the bare search string doesn't
have, so `first()` found nothing, `updateOrCreate` tried to **insert**,
and that tripped the `(provider_id, date)` unique constraint, throwing
a `UniqueConstraintViolationException` that surfaced as a 500 the
original row was never touched by. The `prohibited_if` guess from
earlier this session was solving a real but different, non-blocking
problem — it's still in and still correct, it just wasn't the cause.

Fixed by replacing the `updateOrCreate` call with an explicit
`whereDate('date', ...)->first()` lookup followed by `update()` or
`create()`. Re-ran the full suite locally in-sandbox after: **87
passed, 0 failed** (also had to `npm install` + `npx vp build` to
regenerate the stale Vite manifest/Wayfinder types for
`availability.tsx`, otherwise one Inertia-render assertion 404s on its
own — unrelated to the bug, just this sandbox not having been built
since the page was added). Also ran `vendor/bin/pint --test` on the
touched files — clean.

### Database changes
None.

### Security impact
None.

### Decisions made
Both attempts recorded in `DECISIONS.md` — including that the first
one didn't fix the actual bug, so the failure mode is visible if this
comes up again.

### Next exact task
Pull `AvailabilityController.php` (the real fix) into place, re-run
`php artisan test` once more locally to confirm the same 87/87 result
on your machine/database (this was verified against SQLite in-memory,
same as your run — should match exactly). If green: click through
Settings → Availability by hand once, commit, then Phase 1 Step 3 —
WhatsApp Inbound Pipeline.

### Notes / blockers
Still no PHP in this environment — apt's `noble-updates` mirror is
currently serving 404s for the `php8.3-*` packages needed for a CLI
install. Everything in this entry is reasoned from the failure output
you pasted, not re-executed here.