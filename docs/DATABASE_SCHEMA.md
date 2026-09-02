# WAQTI — TARGET DATABASE SCHEMA

Status: Target design blueprint. Not all tables are created during
Phase 1.

## 1. Database standards

- PostgreSQL
- snake_case, singular model / plural table per Laravel convention
- foreign keys, explicit indexes, explicit constraints
- timestamps
- monetary values use DECIMAL, never floating point
- stable machine-readable enum/state values
- phone numbers stored E.164, indexed

## 2. Foundation tables

### providers

Phase 1 assumes a single row, but modeled as a table (not a config file)
since a second provider is plausible later.

- id
- name
- business_category
- timezone
- whatsapp_phone_number_id (Meta's phone number ID)
- whatsapp_business_account_id
- whatsapp_access_token — encrypted at rest
- dashboard user credentials (or FK to a `users` table if Breeze is used)
- created_at
- updated_at

### clients

- id
- provider_id
- phone_number — E.164, unique per provider
- display_name nullable
- consent_status (`opted_in`, `unknown`) — see SOURCE_OF_TRUTH §2.6
- first_contacted_at
- created_at
- updated_at

## 3. Scheduling

### availability_rules

- id
- provider_id
- weekday
- starts_at
- ends_at
- slot_length_minutes
- is_active
- created_at
- updated_at

### slot_exceptions

- id
- provider_id
- date
- is_closed
- override_starts_at nullable
- override_ends_at nullable
- reason nullable
- created_at

### bookings

- id
- provider_id
- client_id
- starts_at
- ends_at
- status — `pending` / `confirmed` / `cancelled` / `completed` / `no_show`
- source — `whatsapp` / `manual`
- cancelled_at nullable
- cancellation_reason nullable
- created_at
- updated_at

Constraints:

- no two `confirmed`/`pending` bookings for the same provider may overlap
  in time (enforced at the application level with locking; a DB-level
  exclusion constraint is worth evaluating once Postgres extensions are
  confirmed available)

## 4. Messaging

### inbound_messages

- id
- provider_id
- client_id
- whatsapp_message_id — unique, this is the idempotency key
- body
- payload_json — raw webhook payload for debugging
- received_at
- processed_at nullable

### outbound_messages

- id
- provider_id
- client_id
- booking_id nullable
- whatsapp_message_id nullable
- template_name nullable
- category — `utility` / `marketing` / `authentication` / `service`
- status — `queued` / `sent` / `delivered` / `read` / `failed`
- cost_amount nullable
- cost_currency nullable
- sent_at nullable
- created_at

### conversation_states

- id
- client_id — unique (one active state per client)
- provider_id
- state_key
- context_json
- updated_at

## 5. Reminders

### reminder_logs

- id
- booking_id
- type — `24h` / `2h` / other configured offset
- scheduled_for
- sent_at nullable
- outbound_message_id nullable
- created_at

## 6. Future — do not build in Phase 1

### staff — future, only if multi-staff becomes real

- id
- provider_id
- name
- whatsapp-visible display name
- active

### locations — future, only if multi-location becomes real

- id
- provider_id
- name
- address

### subscriptions — future, Waqti's own SaaS billing (see BILLING.md)

- id
- provider_id
- plan
- status
- current_period_ends_at
