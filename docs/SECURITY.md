# WAQTI — SECURITY REQUIREMENTS

## 1. Security objective

A provider must be able to trust Waqti with their client list, booking
history, and WhatsApp credentials. The client, who never signs up for
anything, must be able to trust that their phone number and messages
aren't exposed or misused.

## 2. Authentication (provider dashboard)

Required:

- secure password storage
- login / logout
- password reset
- session invalidation
- CSRF protection
- secure cookies
- appropriate session timeout

## 3. WhatsApp credential security

- `whatsapp_access_token` and any Cloud API secrets are encrypted at
  rest, never logged in plaintext.
- Token rotation procedure documented before go-live, not after an
  incident.
- Webhook verify token stored as a secret, never hardcoded.

## 4. Webhook authenticity

Every inbound webhook request must have its `X-Hub-Signature-256`
verified against the app secret before any processing occurs. A request
that fails verification is rejected, not logged-and-processed.

## 5. Idempotency as a security property, not just a correctness one

A replayed or maliciously-resent webhook must not be able to trigger a
duplicate booking, duplicate reminder spend, or duplicate outbound
message. Deduplication by `whatsapp_message_id` is mandatory on every
inbound handler.

## 6. Client data handling

- Phone numbers and names are personal data. Treat them accordingly even
  at small scale.
- A client's data is scoped to the provider they messaged — if/when
  multi-provider hosting arrives (Phase 2), this becomes a hard tenant
  isolation boundary, same discipline as Velora's `organization_id`
  scoping.
- No client-facing export or lookup surface in Phase 1 (no public API).

## 7. Mass assignment

Use explicit validation. Never allow request payloads to set protected
fields such as:

- provider_id
- consent_status
- booking status transitions that bypass the state machine

## 8. Rate limiting

At minimum:

- dashboard login
- password reset
- the webhook endpoint itself (protect against flood, independent of
  Meta's own delivery behavior)

## 9. Financial/cost security

- `outbound_messages.cost_amount` is written by the system based on
  Meta's reported category/pricing, never client-editable.
- Monthly cost totals must be reconcilable against Meta's billing
  dashboard — a silent mismatch is a signal something is wrong in the
  logging path, not something to shrug off.

## 10. Secrets

Secrets never committed. `.env` locally, secure secret storage in
deployment. `.env.example` contains placeholders only.

## 11. Security testing

Every new mutation gets an authorization test. The webhook handler gets:

- signature-verification-rejects-invalid-signature test
- duplicate-delivery-does-not-duplicate-side-effects test

## 12. Incident response

If a vulnerability, credential leak, or WhatsApp number issue is
suspected:

1. contain
2. preserve evidence (including relevant webhook logs)
3. disable affected capability if required
4. determine affected provider(s)/clients
5. fix
6. rotate credentials if necessary
7. verify
8. document postmortem
