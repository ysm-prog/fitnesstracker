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

## Errors were mapped by exception class, and that silently failed

A policy returning `Response::denyAsNotFound()` produced a 500 rather than a
404. Laravel's handler converts several exception types before any renderable
callback sees them — an `AuthorizationException` carrying a status becomes a
plain `HttpException` with only that status — so a `match` on exception classes
matched nothing and fell through to the default.

**Decision:** map by HTTP status, not by class. `ApiError::fromStatus()` owns the
status-to-code table. The class-based arms that remain are only the ones that
carry extra data, such as validation errors.

## Reordering a program one row at a time collides with its own unique index

`(workout_template_id, position)` is unique, so assigning final positions
sequentially fails the moment two exercises swap: the first update lands on a
position the second still occupies.

**Decision:** `ProgramExerciseSequencer::reorder()` moves every row out of the
way first — a single update adding an offset past the highest position — and
then assigns final positions. Both passes are in one transaction. The swap case
has its own test, because it is the one a naive implementation passes on three
items and fails on two.

## A model default is not a column default

A row created and returned in the same request read back with nulls where the
database had defaults: `is_active`, `training_level`, and the rest exist in the
schema but not on a model instance that has never been reloaded.

**Decision:** mirror column defaults in the model's `$attributes`. The column
default remains the backstop for anything that writes without the model.

## Vercel cannot host the API

No official PHP runtime, and the structural problems outlast any runtime: no
persistent filesystem for the `storage/` tree, and no long-running process for
the queue worker Milestone 7 needs.

**Decision:** split the deployment. Next.js on Vercel at the apex domain, the
Laravel API on Laravel Cloud at `api.`. Because both sit under one registrable
domain, `SESSION_DOMAIN=.<domain>` keeps the session cookie same-site and
first-party cookie authentication is unchanged — which is a better answer to B3
than the single deployable originally assumed.

## `localhost` and `127.0.0.1` are different sites

Running the API on `127.0.0.1:8000` and the front end on `localhost:3000` made
every request look unauthenticated. They are distinct sites to a browser, so a
SameSite=Lax session cookie is never sent between them.

**Decision:** both halves use `localhost` in development, and `.env.example`
says why. It is the same failure that `SESSION_DOMAIN` misconfiguration causes
in production, so it is documented in `docs/deployment.md` as one symptom with
four causes.

## Wrapping an input in its label breaks the field's name

The first `Field` component wrapped the control in a `<label>`. The accessible
name is then computed from everything inside — so a hint became part of the
field's name, and an error message renamed the field the moment it appeared. It
surfaced as a Playwright locator failing to find "Password".

**Decision:** associate explicitly with `htmlFor`/`id`, and attach the hint and
error with `aria-describedby`. The test failure was a real accessibility defect
reporting itself, not a selector to work around.

## The framework validator scans dependency documentation

`vendor/nette/*/AGENTS.md` reference their own `vendor/nette/utils/docs/internals.md`, which broke
CI as soon as Composer ran.

**Decision:** exclude dependency directories from the documented-path scan. See
`docs/decisions/ADR-0003-vendored-framework-validator.md`.
