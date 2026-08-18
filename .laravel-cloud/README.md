# Laravel Cloud settings

Laravel Cloud is configured in its dashboard rather than from a file in the
repository, so this is the reference for what to set, not something the
platform reads.

## Build command

```bash
composer install --no-dev --optimize-autoloader
```

## Deploy command

```bash
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\SystemExerciseSeeder --force
php artisan config:cache
php artisan route:cache
php artisan event:cache
```

The seeder is idempotent — it updates system exercises in place rather than
duplicating them — so it is safe on every deploy.

## Processes

| Process | Command | Why |
| --- | --- | --- |
| Web | (platform default) | Serves the API |
| Worker | `php artisan queue:work --queue=default --tries=3 --max-time=3600` | Milestone 7 runs workout analysis off the request path |
| Scheduler | `php artisan schedule:run` every minute | Nothing scheduled yet; in place before it is needed |

## Health check

`/up` — Laravel's built-in health endpoint, already routed.

## Environment

Everything in `.env.example`, with these production values:

| Variable | Value |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://api.<your-domain>` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | Supabase pooler host for the app; direct host for migrations |
| `DB_PASSWORD` | From Supabase → Project Settings → Database. Not in this repository |
| `FRONTEND_URL` | `https://<your-domain>` |
| `CORS_ALLOWED_ORIGINS` | `https://<your-domain>` |
| `SANCTUM_STATEFUL_DOMAINS` | `<your-domain>` |
| `SESSION_DOMAIN` | `.<your-domain>` — the leading dot is what shares the cookie with the api subdomain |
| `SESSION_SECURE_COOKIE` | `true` |
| `SESSION_DRIVER` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `MAIL_MAILER` | A real transport. Password reset and verification do nothing on `log` |

## The one that is easy to get wrong

`SESSION_DOMAIN` must be the apex with a leading dot, not the api subdomain.
Without it the cookie is scoped to `api.<your-domain>` alone, the browser never
sends it from the front end, and every request looks unauthenticated with no
error to explain why.
