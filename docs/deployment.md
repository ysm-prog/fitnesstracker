# Deployment

## Shape

Three pieces, on three services, under one domain.

| Piece | Service | Address |
| --- | --- | --- |
| Next.js front end | Vercel | `https://<domain>` |
| Laravel API | Laravel Cloud | `https://api.<domain>` |
| PostgreSQL | Supabase (`fitnesstracker-syd`, `ap-southeast-2`) | — |

Vercel cannot host the API. It has no official PHP runtime, and the deeper
problems are structural rather than a matter of runtime: no persistent
filesystem for the `storage/` tree, and no long-running process for the queue
worker that Milestone 7's analysis pipeline needs.

## Why the api subdomain, and not a separate hostname

Both halves sit under one registrable domain so the session cookie stays
**same-site**. `SESSION_DOMAIN=.<domain>` shares the cookie between the apex and
the api subdomain, and Sanctum's first-party cookie authentication works with no
cross-site CSRF workaround. On an unrelated hostname the browser would treat the
cookie as third-party and the application would have to move to bearer tokens
with a refresh story. This is the answer to open question B3.

## Vercel

`vercel.json` at the repository root sets the build to `frontend/`. In the
Vercel project:

- Root directory: repository root (the config handles `frontend/`).
- Environment variable: `NEXT_PUBLIC_API_URL=https://api.<domain>`.
- Domain: the apex, plus `www` if wanted.

Preview deployments get their own hostnames. They reach the API only if the API
allows them, which is deliberately off by default — see
`CORS_ALLOWED_ORIGIN_PATTERNS` in `.env.example`. Point previews at a staging
API rather than production.

## Laravel Cloud

Build, deploy, process, and environment settings are in
`.laravel-cloud/README.md`. That file is a reference for what to configure in
the dashboard; Laravel Cloud does not read it.

The database password comes from Supabase → Project Settings → Database. It is
shown once at project creation, is not retrievable through the management API,
and is not in this repository.

## Database

Laravel migrations are the source of truth. The hosted schema was applied as
Supabase migrations recording the same DDL, with the `migrations` table seeded so
`php artisan migrate` treats them as already run.

```bash
php artisan migrate:status   # what the database thinks has run
php artisan migrate --force  # apply anything new
```

## Release checklist

1. `./vendor/bin/pint --test`
2. `php artisan test` — on SQLite and on PostgreSQL
3. `composer audit`
4. `cd frontend && pnpm lint && pnpm typecheck && pnpm test && pnpm build`
5. `php artisan migrate --force`
6. Confirm `/up` on the API returns healthy
7. Confirm `APP_DEBUG=false` and `SESSION_SECURE_COOKIE=true`
8. Sign in on the deployed front end — the one check that proves cookies, CORS,
   and CSRF all agree

## The failure that will waste an afternoon

If sign-in appears to succeed and every later request comes back
`unauthenticated`, the session cookie is not reaching the API. In order of
likelihood:

1. `SESSION_DOMAIN` is `api.<domain>` instead of `.<domain>`.
2. `SANCTUM_STATEFUL_DOMAINS` does not list the front end's host.
3. `CORS_ALLOWED_ORIGINS` does not list the front end's origin, or is `*` —
   browsers refuse to send credentials to a wildcard origin.
4. `SESSION_SECURE_COOKIE=true` while testing over plain HTTP.

Locally the same trap appears as `localhost` versus `127.0.0.1`: they are
different sites to a browser, so the front end and API must use the same one.

## Rollback

Migrations are additive and reversible via `down()`. Prefer a forward fix for
anything that cannot be reversed safely. Vercel keeps previous deployments and
can promote one instantly. Confirm Supabase's point-in-time recovery window
before relying on it, and test a restore rather than assuming it works.

## Not yet configured

Log shipping, uptime alerting, and backup verification. Milestone 13.
