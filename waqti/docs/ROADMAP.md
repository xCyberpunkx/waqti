# WAQTI — MASTER DEVELOPMENT ROADMAP

This is the ordered development map. Phases are intentionally broader than
the current MVP so the project never loses the destination.

## Phase 0 — Project Foundation

### Deliverables

- GitHub repository
- Laravel application
- Inertia + React + TypeScript
- PostgreSQL + Redis (Docker Compose)
- Pest
- Meta Developer account + WhatsApp Business Account provisioned
- CI baseline
- documentation system

### Exit criteria

- clean local install
- app boots
- test suite boots
- a test message can be sent through the Cloud API sandbox number
- documentation exists

---

# Phase 1 — MVP / Single Provider

This is the first commercially meaningful slice: one real provider running
real bookings through WhatsApp.

## Step 1 — Provider + Auth

- provider record
- dashboard login (Breeze or equivalent)
- WhatsApp credentials stored (encrypted)

## Step 2 — Availability

- define working hours
- slot length configuration
- exceptions (closures, extra hours)
- computed available-slots endpoint

## Step 3 — WhatsApp Inbound Pipeline

- webhook endpoint
- signature verification
- queue inbound messages
- idempotency by `whatsapp_message_id`
- conversation state machine skeleton

## Step 4 — Booking Flow via WhatsApp

- greeting / menu
- show available slots
- confirm selection
- create booking
- confirmation message back to client
- double-booking prevention under concurrent requests

## Step 5 — Reminders

- scheduled job (Redis queue)
- template message send at configured offsets
- delivery status tracking via webhook status callbacks

## Step 6 — Provider Dashboard

- today's bookings
- upcoming bookings
- manual booking entry (phone-in clients)
- cancel/reschedule from dashboard

## Step 7 — Cost Accounting

- every outbound template message logged with category and cost
- monthly cost summary visible to you (not necessarily the provider yet)

### Phase 1 exit criteria

A real provider can:

1. connect their WhatsApp number
2. set their availability
3. have a client book a slot entirely through WhatsApp
4. receive a reminder before the appointment
5. manage bookings from a simple dashboard

and message idempotency + double-booking prevention are tested and hold
under concurrent load.

---

# Phase 2 — Reliability & Retention

Only begin after Phase 1 is running for at least one real, paying
provider.

## 2.1 No-show handling

- mark no-show
- optional no-show pattern flagging per client

## 2.2 Rescheduling

- client-initiated reschedule via WhatsApp
- provider-initiated reschedule with client notification

## 2.3 Multi-provider hosting

- only if a second paying provider is real
- introduces the `organization_id`-style boundary deliberately deferred
  in Phase 1 — needs its own ADR when it happens, same discipline as
  Velora's tenant isolation work

## 2.4 Billing automation

- provider subscription billing (Waqti's own SaaS revenue)
- message-cost pass-through or bundling decision

---

# Phase 3 — Depth

## Multi-staff

- staff profiles
- per-staff availability
- staff selection in the booking flow

## Other channels

- SMS fallback for undeliverable WhatsApp messages
- evaluate only if a real provider requests it

## Analytics

- no-show rate
- booking volume trends
- busiest hours
