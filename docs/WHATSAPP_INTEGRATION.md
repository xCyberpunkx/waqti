# WAQTI — WHATSAPP INTEGRATION

This is the highest-risk area of the product — get it wrong and either
bookings silently fail or the number gets banned. It gets its own
document rather than living inside a generic integrations file.

## 1. Official API only

Waqti uses the **WhatsApp Cloud API** (Meta-hosted), not the unofficial
WhatsApp Business App API, and not libraries that automate a real
WhatsApp Web session (whatsapp-web.js, Baileys, etc.).

Unofficial libraries are acceptable only for a local prototype/demo that
never touches a real client's phone number, never in anything a paying
provider depends on. Rationale: those libraries operate against
WhatsApp's Terms of Service, and a banned number mid-contract with a
paying client is a far worse failure than slower official onboarding.

## 2. Messaging pricing model — verify before pricing Waqti itself

Meta's WhatsApp Business Platform pricing has changed over time (moved
from conversation-based to per-message pricing in 2025) and varies by
country and message category. Do not hardcode assumed per-message costs
into `BILLING.md` calculations without checking Meta's current official
pricing page for Algeria/the relevant market first. This document
intentionally does not state a number, because it will go stale.

## 3. Message categories

Every outbound message must be classified, since category affects both
cost and whether it's allowed:

- **Service** — free-form replies within the 24-hour customer service
  window (client messaged first, you're replying).
- **Utility** — template messages for transactional purposes (booking
  confirmation, reminder). Requires a pre-approved template.
- **Marketing** — template messages for promotional content. Requires
  pre-approved template and, per Meta policy, stricter opt-in handling.
  Waqti Phase 1 should not send marketing-category messages at all.
- **Authentication** — OTP-style codes. Not currently needed by Waqti's
  domain.

Booking confirmations and reminders are **utility** category.

## 4. The 24-hour window

A business can send free-form messages to a client only within 24 hours
of the client's last message. Outside that window, only pre-approved
template messages are allowed. This directly shapes the booking flow and
the reminder system:

- A reminder sent 24h+ after the client's last message **must** be a
  template message, not free text.
- Design the conversation state machine assuming the window will usually
  be closed by reminder time — never assume free-form send will work.

## 5. Template approval

Templates (confirmation message, 24h reminder, 2h reminder, cancellation
notice) must be submitted to Meta for approval before use. Approval time
is not instant — build this into the Phase 1 timeline, not an
afterthought at Step 5.

## 6. Webhook handling

- Verify the webhook signature (`X-Hub-Signature-256`) on every inbound
  request. Reject unsigned or invalid-signature payloads outright.
- Every inbound webhook delivery is queued immediately and acknowledged
  fast (Meta expects a quick 200); processing happens asynchronously.
- Deduplicate by `whatsapp_message_id` before processing — Meta may
  redeliver the same webhook.
- Log the raw payload (`inbound_messages.payload_json`) for debugging,
  since conversation-state bugs are much easier to diagnose with the
  original payload in hand.

## 7. Outbound delivery status

Track delivery via Meta's status webhooks (`sent` → `delivered` →
`read`, or `failed`). A `failed` reminder should be visible on the
provider dashboard, not silently dropped — a provider needs to know a
client never got their reminder.

## 8. Rate limits

Cloud API messaging limits scale with your business's quality rating and
messaging tier. Do not build any bulk-send feature (e.g. "remind
everyone") without checking current tier limits — this is a governance
concern, not just a technical one.

## 9. Number quality / ban risk

- Never send unsolicited marketing-category messages.
- Respect opt-out requests immediately (`consent_status` update, no
  further non-service messages).
- Keep the ratio of user-initiated to business-initiated conversations
  healthy — WhatsApp monitors this per business account.

## 10. What NOT to build in Phase 1

- Bulk marketing sends
- Auto-reply beyond the fixed booking flow (no open-ended AI chat)
- Multi-number/multi-business routing on one Cloud API app, until
  Phase 2's multi-provider work is real
