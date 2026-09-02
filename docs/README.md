# Waqti Documentation System

This directory is the engineering memory of Waqti.

## What Waqti is

A WhatsApp-native booking and no-show-reminder tool for solo and small service
providers (barbers, hairdressers, small clinics, tutors) in Algeria who won't
buy a full ERP but lose real money to manual DM booking and no-shows.

## Purpose

These documents exist so a new Claude/engineer can open a fresh conversation
with:

1. the latest repository ZIP
2. these Markdown files
3. the current `PROGRESS.md`

and continue development without inventing architecture, changing
terminology, or quietly turning this into Velora-lite.

## Source-of-truth hierarchy

1. `SOURCE_OF_TRUTH.md` — permanent principles and non-negotiable rules.
2. `DOMAIN_MODEL.md` — business concepts and their rules.
3. `DATABASE_SCHEMA.md` — target data model and planned fields.
4. `ROADMAP.md` — ordered implementation phases.
5. `WHATSAPP_INTEGRATION.md` — Cloud API integration rules.
6. `SECURITY.md` — security requirements.
7. `TESTING.md` — verification requirements.
8. `BILLING.md` — subscription and message-cost accounting.
9. `DEVOPS.md` — environments, deployment, backup.
10. `GITHUB_WORKFLOW.md` — source control and engineering workflow.
11. `DECISIONS.md` — deliberate architectural decisions.
12. `LATER.md` — intentionally deferred ideas.
13. `PROGRESS.md` — what is actually implemented today.
14. `FOUNDATION.md` — practical build doc, stack, MVP scope.
15. `CLAUDE_HANDOFF.md` — session-start context.

## Important

A document describing a future phase is a design commitment, not permission
to build that phase early.

`ROADMAP.md` defines the destination. `PROGRESS.md` defines reality.

Never claim a feature exists because it is described in a design document.
