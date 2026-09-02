# Waqti

**WhatsApp-native booking, without asking anyone to install anything.**

Waqti turns a service provider's WhatsApp number into a real booking
system. A client messages the number, sees available times, picks one,
and gets a reminder before their appointment — all inside a conversation
they were already going to have anyway.

## The problem

Solo and small service providers — barbers, hairdressers, small clinics,
tutors — mostly run bookings through manual WhatsApp DMs today. It works,
until it doesn't:

- slots get double-booked because nothing is actually checking
- no-shows cost real money, because nobody reminds anyone
- the provider becomes their own scheduling assistant, permanently

A full booking platform or CRM is overkill for this. The provider doesn't
want a new app, and neither does their client.

## What Waqti does

- **Client-side:** book entirely through WhatsApp — no app, no account,
  no download.
- **Provider-side:** a lightweight dashboard to set availability, see
  today's and upcoming bookings, and add walk-in/phone-in clients
  manually.
- **Reminders:** automated messages before an appointment, sent as
  proper WhatsApp template messages, tracked for delivery.
- **No double-booking:** slot availability is checked and locked at the
  moment of booking, not assumed safe.

## What Waqti is not

- Not a CRM.
- Not a marketplace.
- Not (yet) multi-tenant SaaS — it starts single-provider, single
  deployment, on purpose. Multi-provider hosting is a real future phase,
  not a speculative one built early.
- Not an open-ended AI chatbot — the WhatsApp conversation follows a
  fixed booking flow, not free-form AI replies.

## Stack

| Layer | Choice |
|---|---|
| Backend | Laravel 13 |
| Dashboard | Inertia + React + TypeScript |
| Database | PostgreSQL |
| Queue | Redis |
| Messaging | WhatsApp Cloud API (official Meta integration) |
| Testing | Pest |

## Status

Early stage — Phase 0 (project foundation). See [`docs/PROGRESS.md`](docs/PROGRESS.md)
for exactly what's built versus what's designed but not yet implemented.
The `docs/` folder holds the full engineering documentation set (domain
model, database schema, roadmap, security requirements, WhatsApp
integration rules) this project is being built against.

## Where this is headed first

One real provider, running real bookings, entirely through WhatsApp,
with reminders that actually cut no-shows. Everything after that —
multi-staff, multi-location, other channels — is deliberately deferred
until that first slice is proven. See [`docs/LATER.md`](docs/LATER.md)
for what's intentionally not being built yet.
