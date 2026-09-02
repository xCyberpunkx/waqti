# WAQTI — DOMAIN MODEL

## 1. Modeling philosophy

Waqti models a real booking workflow conducted over a messaging channel,
not a generic CRUD calendar.

## 2. Core identity

### Provider

The business/individual using Waqti. Phase 1 assumes exactly one Provider
per deployment.

Responsibilities:
- business identity (name, category, timezone)
- WhatsApp Business phone number + Cloud API credentials
- working hours / availability rules
- dashboard login

### Client

The person booking with the Provider, identified by WhatsApp phone
number. Not an authenticated user of Waqti — Waqti never asks a client to
create an account or install anything.

Contains:
- phone number (E.164)
- display name (from WhatsApp profile or self-provided)
- consent/opt-in status
- booking history

## 3. Scheduling

### Slot / Availability rule

Defines when the Provider *can* be booked. Two representations:

- **AvailabilityRule** — recurring pattern (e.g. Mon–Sat, 09:00–18:00,
  slot length 30 min)
- **SlotException** — one-off closure or addition (holiday, extra hours)

Actual bookable slots are computed from the rule + exceptions minus
existing bookings, not stored as a giant pre-generated table.

### Booking

A confirmed appointment between Provider and Client.

Target attributes:
- client_id
- starts_at
- ends_at
- status (`pending`, `confirmed`, `cancelled`, `completed`, `no_show`)
- source (`whatsapp`, `manual` — provider entered it directly)
- created_at / updated_at
- cancelled_at, cancellation_reason nullable

A Booking is the thing double-booking prevention protects. Two Bookings
for the same Provider must never overlap in time.

## 4. Messaging

### InboundMessage

A message received from a Client via the WhatsApp webhook.

Target attributes:
- whatsapp_message_id (Meta's ID — the idempotency key)
- client_id
- body / payload
- received_at
- processed_at nullable
- conversation_state at time of receipt

### OutboundMessage

A message sent to a Client — booking confirmations, reminders, replies.

Target attributes:
- client_id
- booking_id nullable
- template_name nullable (template messages outside the 24h window)
- category (`utility`, `marketing`, `authentication`, `service` — see
  `WHATSAPP_INTEGRATION.md`)
- whatsapp_message_id
- status (`queued`, `sent`, `delivered`, `read`, `failed`)
- cost_amount, cost_currency
- sent_at

### ConversationState

The current step of a Client's in-progress booking flow (e.g.
`awaiting_service_selection`, `awaiting_slot_confirmation`). This is
short-lived state, not permanent history — it drives what the next
inbound message means.

## 5. Reminders

### ReminderLog

Record of a reminder that was scheduled and/or sent for a Booking.

Target attributes:
- booking_id
- type (`24h`, `2h`, or configurable)
- scheduled_for
- sent_at nullable
- outbound_message_id nullable

Exists as its own record (not just a queued job) so a missed/failed
reminder is visible and auditable, not silently lost.

## 6. Cross-domain rule

Every relation between domains must have a reason.

```text
Client
 ↓
Booking
 ↓
ReminderLog → OutboundMessage
```

A Booking does not duplicate the Client's phone number redundantly beyond
what's needed to send messages without an extra join in the hot path.

## 7. State philosophy

```text
pending
  ↓
confirmed
  ├── cancelled
  ├── completed
  └── no_show
```

The exact allowed transitions must be tested. A `completed` or `no_show`
Booking is terminal — no further reminders are scheduled against it.
