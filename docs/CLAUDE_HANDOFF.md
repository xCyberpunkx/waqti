# WAQTI — CLAUDE HANDOFF / CONTEXT

## How to use this repository

You are continuing an existing (early-stage) implementation, not starting
a new product from zero — the doc set already encodes real decisions.

Before writing code:

1. read `SOURCE_OF_TRUTH.md`
2. read `DOMAIN_MODEL.md`
3. read `DATABASE_SCHEMA.md`
4. read `ROADMAP.md`
5. read `WHATSAPP_INTEGRATION.md`
6. read `SECURITY.md`
7. read `TESTING.md`
8. read `PROGRESS.md`
9. inspect the actual repository
10. compare documentation to code before making claims

## Critical distinction

Design documents describe intended architecture. `PROGRESS.md` describes
what is actually implemented.

If the two conflict, inspect the repository and then update
`PROGRESS.md`. Keep this file (`CLAUDE_HANDOFF.md`) itself current too —
Velora's version of this file went stale for weeks and caused a fresh
session to give wrong guidance; don't repeat that here.

## Do not

- invent features
- invent database tables
- build multi-tenant/multi-provider architecture before Phase 2's
  trigger condition is real (see `SOURCE_OF_TRUTH.md` §2.9)
- build future phases early
- use an unofficial WhatsApp library for anything a real client depends
  on
- trust frontend authorization
- skip webhook signature verification
- mark untested code as complete
- say Redis/Docker is configured when it is not
- hardcode assumed WhatsApp message pricing — verify current rates

## Current task

Phase 0 is done, including the WhatsApp sandbox exit criterion — a real
test message was sent and received against a provisioned Meta test
number, credentials saved in Settings → Business.

Phase 1 Step 1 (Provider + Auth) is done and committed.

Phase 1 Step 2 (Availability) is done, verified for real (a working
PHP 8.4 interpreter got installed into the sandbox this doc is written
from — Ubuntu 24.04 only ships 8.3, this project needs 8.4, see
`PROGRESS.md`'s fifth 2026-09-03 entry for how), and committed. Took
two debugging rounds to actually land — first guess
(`prohibited_if:is_closed,true` type coercion) was wrong; the real bug
was `updateOrCreate` not reliably matching a `date`-cast column. Both
attempts are in `DECISIONS.md` on purpose, so the failure mode is
visible if something like it comes up again.

Phase 1 Step 3 (WhatsApp Inbound Pipeline) is done and verified for
real: webhook signature verification, the verification handshake, and
`ProcessInboundWhatsappMessage` (idempotent, provider-scoped, skeleton
`ConversationState` only — no actual conversation logic). Full suite:
102 passed. See `PROGRESS.md`'s sixth 2026-09-04 entry.

Immediate next steps, before Step 4:
1. User needs to add real `WHATSAPP_APP_SECRET` /
   `WHATSAPP_WEBHOOK_VERIFY_TOKEN` to their local `.env` (never
   `.env.example`) and configure the webhook subscription in the Meta
   App Dashboard — this can't be done from here.
2. `php artisan migrate` for the two new tables, then a real end-to-end
   check: send a WhatsApp message, confirm it lands in
   `inbound_messages` with a `Client` and `ConversationState` created.
3. Commit.

After that: **Phase 1 Step 4 — Booking Flow via WhatsApp**. This is
where `ComputeAvailableSlots` (Step 2) and the inbound pipeline (Step
3) actually meet — greeting/menu, show available slots, confirm
selection, create a real `Booking`, confirmation message back,
double-booking prevention under concurrent requests. `ConversationState.state_key`
gets its real vocabulary here for the first time; don't let it stay
`'idle'`-only past this step.

Once that's green: Phase 1 Step 3 — WhatsApp Inbound Pipeline.

## Required implementation sequence (once Phase 0 is done)

```text
provider + availability
 ↓
webhook inbound pipeline (signature + idempotency first)
 ↓
booking flow state machine
 ↓
reminders
 ↓
dashboard
 ↓
cost accounting
```

Do not build the booking flow before the webhook pipeline's idempotency
handling is tested — a booking flow on top of an unreliable inbound
pipeline will silently double-book or double-reply.

## If uncertain

Priority order:

1. Source of Truth
2. Domain Model
3. Database Schema
4. Roadmap
5. WhatsApp Integration
6. Security
7. Testing
8. Billing
9. Progress
10. actual repository/code
11. general engineering judgment

When a decision changes architecture, record it in `DECISIONS.md`.

## Required end-of-session update

Update `PROGRESS.md` with: what changed, what was tested, exact next
task, failures/blockers, database changes, security impact.
