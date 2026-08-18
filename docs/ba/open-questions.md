# Open Questions

Questions the repository cannot answer. Each says what is assumed in the
meantime, so work is never blocked on an unanswered question — but the
assumption is visible and can be overruled.

## Answered

**B1 — Which architecture wins, the brief or the vendored framework?**
Answered: the brief. Laravel monolith on Supabase PostgreSQL, with Supabase
Storage for photos. Recorded in `docs/decisions/ADR-0001-laravel-authorization.md`
and `docs/decisions/ADR-0002-user-id-ownership.md`.

**B2 — Which database, and what do integration tests run against?**
Answered: a new Supabase project, `fitnesstracker-syd`, PostgreSQL 17 in
`ap-southeast-2`. Tests run on in-memory SQLite by default and against a
`postgres:17` service container in CI.

## Open

**B3 — One deployable or two?**
Assumed: one. Sanctum cookie authentication for a same-site first-party client,
with bearer tokens available for a native client that names its device. If the
front end is deployed separately from the API, cookie authentication becomes
cross-site and the session configuration has to change. Decide before Milestone
11.

**B4 — Repository layout and package manager for the front end.**
Assumed: Laravel at the repository root, front end to be added under
`frontend/`, package manager not yet chosen. **Now blocking.** Milestone 2's
backend is complete and its user interface — the exercise library and program
builder — cannot start until this is answered. My recommendation is Next.js
under `frontend/` with pnpm, deployed behind the same origin as the API so
Sanctum's cookie authentication stays same-site, which also answers B3.

**B5 — Production database password.**
Supabase shows the database password once, at project creation, and does not
expose it through the management API. It is not in this repository and must be
taken from Project Settings → Database. Until it is set in `.env`,
`php artisan migrate` cannot run against production — the Milestone 1 schema was
applied as SQL instead.

**B6 — Licence.**
Currently MIT, inherited from the Claude framework copy. Confirm this is
intended for a personal health product before anything is published.

**B7 — Mail transport.**
Password reset and email verification are implemented and tested, but
`MAIL_MAILER` is `log`. A real transport is needed before either works for a
human. Decide before Milestone 13.

**B8 — Unilateral recording convention in the user interface.**
The engine handles both `per_side` and `combined` bases. Which one the UI
defaults to, and whether the user can change it per exercise, is a UX decision
for Milestone 3.
