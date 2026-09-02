# FOUNDATION.md
### Waqti — Practical Build Doc
Status: Living document. Update as decisions change. This is not the
constitution — see `SOURCE_OF_TRUTH.md` for the rules that don't change.

---

## 1. What Waqti Is

A WhatsApp-native booking tool for a single service provider (one barber,
one clinic, one tutor) and their clients. A client messages the provider's
WhatsApp number, sees available slots, books, and gets reminders. The
provider gets a lightweight dashboard to manage their schedule.

It is not yet: a multi-staff platform, a multi-tenant SaaS the way Velora
is, or a general CRM. It is one thing done correctly: turning WhatsApp DMs
into a real booking system that reduces no-shows.

## 2. Stack (locked — do not revisit without a real reason)

| Layer | Choice |
|---|---|
| Backend | Laravel 13 |
| Frontend (provider dashboard) | Inertia + React + TypeScript |
| Database | PostgreSQL |
| Queue | Redis — required from day one (webhook processing, reminder scheduling; not deferred like Velora's) |
| Messaging | WhatsApp Cloud API (official, Meta-hosted) |
| Local env | Docker Compose (Postgres + Redis) |
| Testing | Pest |

No unofficial WhatsApp libraries in production (see `WHATSAPP_INTEGRATION.md`
§2). No multi-tenant architecture until a real second provider on the same
codebase forces it — see §3.

## 3. Tenancy model (deliberately different from Velora)

Phase 1 is **single-tenant per deployment**: one provider, one database
(or one clearly-scoped set of rows), one WhatsApp number. Do not build
Velora-style `organization_id` multi-tenancy until there's a real reason
(e.g. you're hosting multiple providers on shared infrastructure). Note
this explicitly so nobody "helpfully" adds multi-tenancy early.

## 4. MVP Entities (and only these)

```
Provider
Client
Slot / Availability
Booking
ReminderLog
InboundMessage
OutboundMessage
```

That's ~7 tables. Everything else (multi-staff, recurring bookings, other
channels) is Phase 2+ and does not get a table until Phase 1 is running
for one real paying provider.

## 5. Phase 1 Scope (what "done" means before touching anything else)

- [ ] Provider onboarding (WhatsApp number connected, business hours set)
- [ ] Availability/slot definition by the provider
- [ ] Inbound WhatsApp message handling (webhook, signature verified)
- [ ] Client booking flow via WhatsApp (see slots, pick one, confirm)
- [ ] Double-booking prevention
- [ ] Automated reminder send (24h and/or 2h before, configurable)
- [ ] Provider dashboard: today's bookings, upcoming, manual booking entry
- [ ] Message idempotency (a webhook retry must never double-book or
      double-send)
- [ ] Cost-tracking per message sent (see `BILLING.md`)

Nothing else ships before this list is fully green.

## 6. Explicitly OUT of scope right now

Do not build, discuss implementing, or create tables for any of these until
Phase 1 is live with a real paying provider:

- Multi-staff / multi-location support
- Recurring bookings
- Payment collection through WhatsApp
- Other channels (SMS, Instagram, Telegram)
- Provider-side multi-tenancy / shared infrastructure billing
- Public API
- Analytics/reporting beyond the basic dashboard
- AI auto-reply beyond the fixed booking flow

If you catch yourself building one of these, stop and ask: does Phase 1
need this to keep one real provider running? If no, it goes on
`LATER.md`, not into code.

## 7. Repo Structure

```
waqti/
├── app/
│   ├── Models/
│   ├── Policies/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Requests/
│   ├── Actions/
│   └── WhatsApp/          ← Cloud API client, webhook handling, templates
├── database/
│   └── migrations/
├── resources/js/          ← provider dashboard, React/TS
├── tests/
│   ├── Feature/
│   └── Security/          ← webhook signature + idempotency tests live here
├── docs/
└── README.md
```

## 8. Build Order — Steps Within Phase 1

- **Step 1 — Provider + Auth.** Single provider record, simple login for
  the dashboard. No org/tenancy layer.
- **Step 2 — Availability.** Provider defines working hours and slot
  length. This is the foundation everything else books against.
- **Step 3 — WhatsApp inbound pipeline.** Webhook receiver, signature
  verification, message queue, idempotency by Meta's message ID.
- **Step 4 — Booking flow via WhatsApp.** Conversational state machine:
  greeting → show slots → confirm → book.
- **Step 5 — Reminders.** Scheduled job, template message send, delivery
  status tracking.
- **Step 6 — Provider dashboard.** View/manage bookings, manual entry for
  phone-in clients.
- **Step 7 — Cost accounting.** Every outbound template message logged
  against its cost category (see `BILLING.md`).

→ **First real provider onboarded here.** Everything after Step 7 is
driven by what that provider actually needs.

## 9. Weekly Check-in (even solo)

Every Friday, answer honestly:
- What actually shipped this week?
- What did I build that wasn't on this list? Why?
- Is Phase 1 closer to done or did scope grow?

If scope grew without a real provider forcing it, re-read §6.
