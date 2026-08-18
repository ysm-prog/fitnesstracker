# Security

## Model

Authentication is Laravel Sanctum. **Authorization is enforced in Laravel
policies**, server-side, one per user-owned model. Row Level Security is not the
authorization boundary — see `docs/decisions/ADR-0001-laravel-authorization.md`
for why, and what that costs.

## Ownership

Ownership is always derived from the authenticated session. It is never read
from the request. Three independent mechanisms enforce this, because one is a
single point of failure:

1. `user_id` appears in no `Fillable` list anywhere in the application.
2. `UpdateFitnessProfileRequest` strips `user_id`, `userId`, and `id` from the
   payload before validation runs.
3. Resources are reached through the authenticated user, so there is no
   identifier in the URL to tamper with in the first place.

`tests/Feature/Security/AuthorizationTest.php` and the client-supplied-`user_id`
test in `tests/Feature/Profile/FitnessProfileTest.php` fail if any of this is
weakened.

## Supabase exposure

Supabase publishes the `public` schema through PostgREST, and grants the `anon`
and `authenticated` roles table privileges by default. Since this application
does not use RLS as its boundary, that would have exposed every user's body
weight to anyone holding the publishable anon key.

Both doors are shut, and the lock was verified rather than assumed:

- RLS is enabled on every table with **no policies**, so no non-bypassing role
  can see a row. The application connects as `postgres`, which has `BYPASSRLS`.
- Privileges are revoked from `anon` and `authenticated`, and default privileges
  are revoked so new tables do not silently inherit them.

Verified by connecting as `anon` and attempting a read: `permission denied for
table users`. Supabase's linter reports `rls_enabled_no_policy` at INFO level
for each table; under this design that is the intended state, not a defect.

See `docs/decisions/ADR-0004-postgrest-exposure.md`.

## Error surface

Every failure leaves through `App\Support\ApiError`. No SQL, stack trace,
exception class, file path, or connection string reaches a client — asserted by
a test that throws an exception whose message deliberately contains a fake
`SQLSTATE`, hostname, and password, and checks none of it appears in the
response.

## Secrets

`.env` is git-ignored, along with `vendor/`, `node_modules/`, build output, and
coverage. `.env.example` declares the configuration surface with no values. A
pattern scan for AWS keys, OpenAI-style keys, JWTs, service-role references,
private key blocks, and inline passwords returns no matches. `composer audit`
runs in CI.

## Not yet addressed

These arrive with the milestone that needs them, and are tracked in
`docs/ba/acceptance-criteria.md`:

- Private object storage and signed URLs for progress photos (Milestone 4).
- CSV formula-injection neutralisation on export (Milestone 12).
- The full IDOR matrix across every user-owned resource (Milestones 2–12).
- CORS origin allowlist tightening at deployment (Milestone 13).
