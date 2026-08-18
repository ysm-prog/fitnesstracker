# Research and Decisions

Findings established by inspection or measurement, with the decision each one
drove. Nothing here is recalled; each was checked at the time it was recorded.

## Versions

`laravel/framework` v13.25.0 requires PHP `^8.3`; the environment has PHP
8.4.19. `laravel/sanctum` v4.3.3. PHPUnit 12.5.33 ships with the skeleton.
`composer audit` reports no advisories. Node 22.22.2 is available for the front
end; the exact front-end versions are deferred to Milestone 2 rather than pinned
before anything uses them.

**Decision:** pin Laravel 13, Sanctum 4, PHPUnit 12. Confirm TypeScript and Next
versions at Milestone 2, not now.

## PHPUnit 12 ignores `@dataProvider`

Annotation-based data providers are not honoured; the run errors with "Too few
arguments". Attributes are required.

**Decision:** `#[DataProvider('method')]` throughout. Recorded because it is the
kind of thing that costs an hour twice.

## SQLite cannot express what the target lifecycle needs

No `SELECT … FOR UPDATE`, no `NULLS NOT DISTINCT`, and no way to add a check
constraint after table creation.

**Decision:** SQLite for the fast default run; PostgreSQL required for the
constraint and concurrency suites, and CI runs both. Check constraints in
migrations are applied only when the driver is `pgsql`.

## Deleting a user resurrected the user

`Auth::logout()` cycles the remember token, which calls `save()` on the user
model. A model whose `exists` flag has just been cleared by `delete()` saves as
an **INSERT**. Signing out after deleting silently re-created the account, and
the endpoint returned 200 while doing it.

**Decision:** sign out first, then delete. The comment in
`app/Http/Controllers/ProfileController.php` explains why the order is not
arbitrary, because it looks arbitrary.

## The default guard is Sanctum, not web

Clearing the session alone left the request still believing it was signed in,
because the Sanctum guard holds the resolved user for the rest of the process.

**Decision:** `Auth::forgetGuards()` after sign-out and after account deletion.

## Supabase publishes the `public` schema

PostgREST exposes `public`, and `anon` and `authenticated` hold table privileges
by default. With authorization in Laravel rather than RLS, that was a live path
around the entire application.

**Decision:** RLS enabled and forced with no policies, plus privileges revoked
from `anon` and `authenticated`, plus default privileges revoked. Verified by
connecting as `anon` and being denied. See
`docs/decisions/ADR-0004-postgrest-exposure.md`.

## The framework validator scans dependency documentation

`vendor/nette/*/AGENTS.md` reference their own `vendor/nette/utils/docs/internals.md`, which broke
CI as soon as Composer ran.

**Decision:** exclude dependency directories from the documented-path scan. See
`docs/decisions/ADR-0003-vendored-framework-validator.md`.
