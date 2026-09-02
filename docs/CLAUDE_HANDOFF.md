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

The current next milestone is Phase 0 — Project Foundation. Nothing has
been built yet. See `PROGRESS.md` → "Next exact task."

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
