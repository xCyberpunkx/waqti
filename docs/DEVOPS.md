# WAQTI — DEVOPS & OPERATIONS

## 1. Current local environment

Current reality: no environment provisioned yet — this section will be
filled in as Phase 0 actually happens. Do not describe infrastructure as
active until it is.

Target local setup:

- Docker Compose (Postgres + Redis)
- Laravel Sail or equivalent
- Meta Developer sandbox app + test WhatsApp number for local webhook
  testing (via a tunnel — ngrok or similar — since Meta requires a public
  HTTPS webhook URL even for local dev)

## 2. Future environments

Minimum target: local, staging, production.

Production must never be the place where migrations or WhatsApp templates
are experimentally developed — template changes go through Meta's
approval process, which has its own lead time to account for in any
deploy plan.

## 3. Backups

- automated database backups
- retention policy
- restore procedure, tested — not assumed to work

## 4. Deployment

1. build
2. tests
3. migration review
4. backup
5. deploy
6. migrate
7. health check (including a live webhook-reachability check)
8. rollback path

## 5. Environment variables

`.env.example` contains safe placeholders. Never commit real Meta app
secrets, access tokens, or webhook verify tokens.

## 6. Queues

Redis-backed queue workers required from day one (not deferred, unlike
Velora's Phase 1). Inbound webhook processing and reminder scheduling
both depend on reliable queue workers running continuously — monitor
failed jobs, retry safely, keep jobs idempotent.

## 7. Scheduler

Laravel's scheduler drives reminder dispatch (checking for
`reminder_logs` due to send). Needs a monitored, always-running cron/
scheduler process in production — a missed scheduler tick means missed
reminders, which is the core value proposition failing silently.

## 8. Monitoring

Production needs, from early on given the reminder-dependency:

- uptime
- queue health (a stalled queue = silently unsent reminders)
- webhook endpoint error rate
- Meta API error rate (rate limits, token expiry)
- application logs

## 9. Disaster recovery

Define RPO/RTO, backup frequency, restore procedure, incident owner —
same as any production system, but note that a WhatsApp number/Business
Account is itself a recovery dependency: losing access to it is a
different kind of incident than a database failure, and needs its own
recovery plan (who has admin access to the Meta Business Manager account,
how it's recovered if lost).

## 10. Migration safety

Prefer additive migrations. Never casually drop columns, rename columns,
or delete data without a tested migration path — same rule as Velora.
