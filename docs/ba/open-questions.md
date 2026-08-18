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

**B3 — One deployable or two?**
Answered: two, under one registrable domain. Next.js on Vercel at the apex, the
Laravel API on Laravel Cloud at `api.`. Sharing a registrable domain keeps the
session cookie same-site, so first-party cookie authentication is unchanged;
bearer tokens remain available for a native client that names its device.

**B4 — Repository layout and package manager for the front end.**
Answered: Laravel at the repository root, Next.js under `frontend/`, pnpm.

## Open

**B9 — The domain.**
The deployment configuration uses `<domain>` placeholders. Real values are
needed for `APP_URL`, `FRONTEND_URL`, `CORS_ALLOWED_ORIGINS`,
`SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`, and `NEXT_PUBLIC_API_URL` before
either half can be deployed.

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
