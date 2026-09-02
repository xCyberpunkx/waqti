# WAQTI — BILLING

Two separate money flows exist here. Never mix them — same discipline as
Velora's client-payment-vs-SaaS-subscription boundary.

## 1. Separate concepts

### WhatsApp message cost (pass-through)

Meta charges per outbound template message (rate varies by category and
country — see `WHATSAPP_INTEGRATION.md` §2, do not hardcode a number
here). This is a real cost Waqti incurs on the provider's behalf.

### Waqti subscription revenue

What the provider pays Waqti monthly to use the product. This is your
actual business model.

## 2. Pricing decision: bundle or pass-through?

Two viable models — decide explicitly, don't default silently:

- **Bundled** — flat monthly fee that includes a message allowance
  (e.g. "up to 200 reminders/month included"), overage billed
  separately. Simpler for the provider to understand, riskier for you if
  usage spikes.
- **Pure pass-through** — subscription fee for the product + exact
  message costs billed at cost or with a markup. More transparent, more
  billing complexity.

Record whichever is chosen, and why, in `DECISIONS.md` once decided —
this is a real architectural/business decision, not a detail.

## 3. Target lifecycle (provider subscription)

```text
trial
  ↓
active
  ↓
past_due
  ↓
grace_period
  ↓
cancelled / expired
```

## 4. Target entities (Phase 2 — not built until subscription billing is
real, i.e. more than one paying provider or a provider past a free
trial)

- subscriptions
- subscription_events
- billing_transactions

Phase 1 can run on manual invoicing (you send the provider a bill
directly) — do not build a billing engine before there's a second
paying customer to justify automating it.

## 5. Cost accounting (Phase 1 — required from day one)

Even before automated billing exists, every outbound message must be
logged with its cost (`outbound_messages.cost_amount`), so that:

- you can reconcile against Meta's actual invoice
- you know, per provider, what their real message cost is before you
  commit to a subscription price
- you catch a runaway cost bug (e.g. a reminder loop) immediately, not at
  month-end

## 6. Billing invariants

- A reminder must never be sent twice for the same booking + offset —
  this is a cost-correctness issue as much as a UX one.
- Manual invoices (Phase 1) must be traceable back to the
  `outbound_messages` cost log, not estimated.

## 7. Pricing Waqti itself — inputs needed before quoting a provider

- real per-message cost for the target market (verify current, don't
  assume)
- expected messages per booking cycle (confirmation + 1–2 reminders +
  any back-and-forth = budget ~3–5 messages per booking as a starting
  estimate, refine once real data exists)
- provider's expected booking volume/month
- competitor pricing for manual booking tools/agencies in the same market
