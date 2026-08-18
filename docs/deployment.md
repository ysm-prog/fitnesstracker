# Deployment

## Environments

| Environment | Database | Notes |
| --- | --- | --- |
| Local | SQLite file or in-memory | `php artisan serve` |
| CI | `postgres:17` service container | Both suites run |
| Production | Supabase `fitnesstracker-syd`, PostgreSQL 17, `ap-southeast-2` | Schema deployed |

## Database credentials

The application connects to Supabase PostgreSQL as `postgres`. **The password is
shown once, when the project is created, and is not retrievable through the
management API** — take it from Project Settings → Database and put it in `.env`
as `DB_PASSWORD`. Nothing in this repository contains it.

Use the connection pooler host for a deployed application; the direct host is
appropriate for migrations and one-off tasks.

## Schema deployment

Laravel migrations are the source of truth. The hosted schema was applied as
Supabase migrations recording the same DDL, and the `migrations` table was
seeded so `php artisan migrate` treats those as already run.

Once `DB_PASSWORD` is set, the normal path applies:

```bash
php artisan migrate --force
```

Verify what is deployed before assuming:

```bash
php artisan migrate:status
```

## Release checklist

1. `./vendor/bin/pint --test`
2. `php artisan test` — on both SQLite and PostgreSQL
3. `composer audit`
4. `php artisan migrate --force`
5. `php artisan config:cache && php artisan route:cache`
6. Confirm `/up` returns healthy
7. Confirm `APP_DEBUG=false` and `SESSION_SECURE_COOKIE=true` in production

## Rollback

Migrations are additive and reversible via `down()`. For a schema change that
cannot be reversed safely, prefer a forward fix. Supabase provides
point-in-time recovery on paid plans; confirm the retention window before
relying on it, and test a restore rather than assuming it works.

## Not yet configured

Hosting, TLS termination, queue worker supervision, log shipping, uptime
alerting, and backup verification. Milestone 13.
