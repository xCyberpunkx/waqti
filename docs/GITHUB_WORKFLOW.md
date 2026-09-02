# WAQTI — GITHUB & ENGINEERING WORKFLOW

## 1. Repository

Single repo, e.g. `waqti` (org optional at this scale — a personal repo
is fine until there's a team).

## 2. Branching

- `main`
- short-lived `feature/*`
- `fix/*`
- `security/*`
- `hotfix/*`

## 3. Main branch

Main should always: install, build, test, pass static checks, contain no
secrets (including no real Meta tokens in test fixtures).

## 4. Commits

Use: `feat`, `fix`, `refactor`, `security`, `test`, `docs`, `perf`,
`chore`.

Examples:

`feat: add availability rule model and slot computation`

`security: verify webhook signature before processing`

`test: cover concurrent booking race condition`

## 5. Pull requests

Every significant change documents: problem, solution, database changes,
security impact, tests, screenshots if dashboard UI changed, and —
specific to this project — whether any WhatsApp template content changed
(since template edits require re-approval from Meta).

## 6. Issues

Suggested labels:
- `priority:p0` / `p1` / `p2`
- `type:bug` / `type:feature` / `type:security`
- `module:booking` / `module:whatsapp` / `module:billing` /
  `module:dashboard`

## 7. Releases

Tag meaningful releases. Don't version-number for the sake of activity.

## 8. Documentation rule

A deliberate architecture change updates: source of truth, relevant
design document, decisions log, progress.

## 9. Definition of complete

No feature is complete from a GitHub perspective until: code committed,
tests committed, migrations reviewed, docs updated, working tree clean,
CI passing.
