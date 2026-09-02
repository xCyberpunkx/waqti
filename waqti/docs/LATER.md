# WAQTI — LATER

This list captures ideas so they don't disappear, while explicitly
keeping them out of current implementation.

## Product

- multi-staff support
- multi-location support
- recurring bookings
- waitlist for fully-booked slots
- client-facing self-service reschedule beyond basic WhatsApp flow
- loyalty/repeat-client perks

## Channels

- SMS fallback for undeliverable WhatsApp messages
- Instagram DM booking
- Telegram booking

## Commerce

- deposit collection at booking time
- full payment collection through WhatsApp/Cloud API payment features
- provider-side invoicing for their own clients

## Intelligence

- no-show prediction/flagging per client
- optimal reminder-timing experimentation
- AI-assisted free-form conversation beyond the fixed booking flow

## Platform

- multi-provider hosted infrastructure (see SOURCE_OF_TRUTH §2.9 — real
  tenancy work, not speculative)
- public API
- webhooks out to third parties (e.g. provider's own calendar sync)
- Google Calendar / Outlook sync

## SaaS

- automated subscription billing (see BILLING.md §4)
- self-serve provider signup/onboarding without you doing it manually
- usage-based tiering

Rule:

Anything in this file does not enter active development unless a roadmap
phase is opened and its requirements are defined.
