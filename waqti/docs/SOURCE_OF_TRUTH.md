# WAQTI — SOURCE OF TRUTH

Status: Living constitution. Changes require an explicit amendment and
reason, recorded in `DECISIONS.md`.

## 1. Product identity

Waqti is a WhatsApp-native booking tool for solo and small service
providers, starting in Algeria. It turns manual DM-based booking into a
structured, reminder-backed flow without asking the client to install
anything or the provider to learn a new app.

Waqti is not a CRM, not a marketplace, and not (yet) multi-tenant SaaS in
the Velora sense. It does one thing: booking + reminders, over WhatsApp.

## 2. Core architectural rules

### 2.1 No table without a domain reason

Every persistent entity must represent a real business concept or an
operational requirement. A dashboard screen is never a sufficient reason
for a table.

### 2.2 No business mutation without authorization

Every mutation is authorized on the server. Frontend permissions are UX
only.

### 2.3 Every inbound WhatsApp event is idempotent

A webhook delivery may arrive more than once. Processing the same Meta
message ID twice must never create a duplicate booking, a duplicate
reminder, or a duplicate outbound message.

### 2.4 Every outbound message is logged and costed

No message leaves the system without a row recording what was sent, to
whom, why, and what category of billable message it was. See
`BILLING.md`. This is not optional instrumentation — it is how the
business stays solvent.

### 2.5 Slot availability is the single source of truth for booking

A client can never be shown or allowed to book a slot that conflicts with
an existing confirmed booking. Race conditions between two near-
simultaneous bookings must be handled with a locking strategy, not
"probably fine."

### 2.6 Consent and opt-in are respected

A client only receives messages because they initiated contact or
explicitly opted in. Do not build any bulk-messaging or re-engagement
feature that sends a template message to a client who hasn't messaged
first or given consent, without checking current WhatsApp Business
Messaging Policy rules.

### 2.7 Status values are domain values

Store stable machine values (`pending`, `confirmed`, `cancelled`,
`completed`, `no_show`), never translated display strings, as state.

### 2.8 Controllers stay thin

Request validation → authorization → application action → domain
operation → response. Webhook handlers are controllers too — the same
rule applies.

### 2.9 Single-tenant until proven otherwise

Do not introduce `organization_id`-style multi-tenancy (see Velora) until
a second provider on shared infrastructure is a real, funded requirement.
Building it speculatively is the exact overengineering this document
exists to prevent.

## 3. Current stack commitment

- Laravel 13
- Inertia + React + TypeScript
- PostgreSQL
- Redis (queue + reminder scheduling — required from day one, not
  deferred)
- WhatsApp Cloud API (official)
- Pest

## 4. Current environment truth

At the current project stage:

- No code has been written yet.
- No WhatsApp Business Account or Cloud API app has been provisioned yet.
- No hosting/deployment target has been chosen yet.

Documentation must describe reality. Do not write that any of the above
is done until it actually is.

## 5. Product principle

Small scope, serious architecture. The webhook idempotency and message
cost-accounting rules are not "later" concerns — they are Phase 1
requirements, because getting them wrong either loses bookings or loses
money on every single message sent.

## 6. Amendment rule

Any change to these rules must record date, changed rule, reason, and
consequences in `DECISIONS.md`.
