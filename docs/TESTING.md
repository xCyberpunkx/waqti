# WAQTI — TESTING STRATEGY

## 1. Testing principle

A feature is not complete because it worked once against the WhatsApp
sandbox number.

## 2. Test layers

- **Unit** — pure business rules (slot computation, overlap detection)
- **Feature** — Laravel application workflows
- **Security** — webhook auth, idempotency, authorization
- **Database** — constraints and transactional integrity around booking
  overlap prevention

## 3. Required Phase 1 tests

### Auth (dashboard)

- login / logout
- password reset
- protected route access

### Availability

- create/update availability rule
- slot computation respects rules + exceptions
- slot computation excludes already-booked times

### Booking

- successful booking creation
- overlapping booking rejected
- concurrent booking attempts for the same slot — only one succeeds
  (this test must actually simulate concurrency, not just sequential
  calls)
- cancel booking
- invalid time range rejected

### WhatsApp inbound pipeline

- valid signature accepted
- invalid signature rejected
- duplicate `whatsapp_message_id` does not reprocess
- malformed payload does not crash the handler

### Booking flow via WhatsApp

- full conversation flow: greeting → slot shown → confirmation → booking
  created
- flow correctly resumes from stored conversation state
- flow handles an unexpected/out-of-order message gracefully

### Reminders

- reminder scheduled at correct offset from booking time
- reminder not sent for cancelled/completed bookings
- reminder send failure is recorded, not silently dropped

### Cost accounting

- every outbound template message produces a corresponding
  `outbound_messages` row with category and cost populated

## 4. Regression suite

After every major change:

- full security suite
- booking-overlap suite
- webhook idempotency suite
- application boot test

## 5. Definition of done

A feature is done when:

- tests exist and pass
- authorization tested
- idempotency tested where inbound/webhook-driven
- validation tested
- failure paths tested
- dashboard UI verified (where applicable)
- documentation updated
