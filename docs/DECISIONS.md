# WAQTI — ARCHITECTURAL DECISIONS

This is the deliberate decision record. A decision belongs here only when
it changes architecture, domain modeling, infrastructure, or long-term
product direction.

## 2026-09-02 — Backend framework bumped to Laravel 13

Changed: `FOUNDATION.md` §2 and `SOURCE_OF_TRUTH.md` §3 locked-stack entries,
from Laravel 12 to Laravel 13.

Reason: [fill in — e.g. Laravel 13 released and is now the current stable
version at project start, no reason to scaffold on 12]

Consequences: none yet — no code exists, so this is a documentation-only
change with no migration path required. Applies from the Phase 0
`laravel new waqti` scaffold onward.

No decisions recorded yet beyond the above — this log starts substantively
once Phase 0 begins.

Expected early decisions to record here once made:

- official Cloud API vs. any prototype-only unofficial library boundary
  (see `WHATSAPP_INTEGRATION.md` §1)
- bundled vs. pass-through message cost pricing (see `BILLING.md` §2)
- exact reminder offsets (24h/2h, or different) once tested against real
  no-show data
- when/if Phase 2 multi-provider tenancy actually gets triggered, and
  what boundary model is chosen (Velora-style `organization_id`, or
  something lighter given the smaller domain)
