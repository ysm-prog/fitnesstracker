# Testing

## Running

```bash
php artisan test                     # everything
php artisan test --filter=LoginTest  # one class
./vendor/bin/pint --test             # formatting
composer audit                       # dependency advisories

cd frontend
pnpm test        # Vitest
pnpm typecheck
pnpm lint
pnpm e2e         # Playwright; both halves must be running
```

## Current state

144 backend tests (528 assertions) against SQLite in memory, 18 front-end tests,
and 2 browser walkthroughs. All passing.

| Suite | Covers |
| --- | --- |
| `tests/Unit/` | Correlation ID handling, loading-type semantics |
| `tests/Feature/Auth/` | Registration, sign-in, sign-out, password reset, email verification |
| `tests/Feature/Profile/` | Account, fitness profile, validation ranges, deletion |
| `tests/Feature/Exercise/` | Library listing, search, filters, pagination, creation, validation, archive and restore, system immutability, seeder idempotency |
| `tests/Feature/Program/` | Programs, duplication, prescriptions, validation ranges, ordering |
| `tests/Feature/Security/` | Policies, IDOR across every identifier-bearing route, anonymous access |
| `tests/Feature/Api/` | Error envelope, correlation IDs, leak protection |
| `frontend/src/lib/__tests__/` | The fetch client and the Zod schemas |
| `frontend/e2e/` | Register → browse the library → build a program, and a rejected prescription, in a real browser at a phone viewport |

## What the browser walkthrough is for

Unit tests cannot show that CORS, the CSRF cookie, the session cookie surviving a
cross-origin request, and the API contract all agree — every one of those is
invisible until a real browser makes a real request to a real API. The
walkthrough is the only test that exercises the seam between the two
deployables, which is exactly where a split architecture fails.

It needs both halves running, so it is not part of the CI job; run it locally
before a release. `PLAYWRIGHT_CHROMIUM_PATH` points it at an existing Chromium
where the machine already has one.

## Why SQLite is not enough

The default run uses in-memory SQLite for speed. That is fine for validation and
authorization, and **not** fine for two things this system depends on:

- `SELECT … FOR UPDATE`, which SQLite does not support, and which atomic target
  activation requires (Milestone 10).
- `NULLS NOT DISTINCT`, which the partial unique index enforcing one active
  target per scope requires, and which needs PostgreSQL 15+.

Check constraints are also PostgreSQL-only in these migrations, because SQLite
cannot add constraints after table creation.

It also has no `NULLS NOT DISTINCT`, so the rule keeping the system exercise
library free of duplicate names exists only on PostgreSQL.

CI therefore runs the suite twice: once on SQLite, once against a `postgres:17`
service container. A constraint or concurrency test that has only run on SQLite
has not been tested.

Milestone 2's PostgreSQL-only constraints were additionally verified directly
against the deployed database by attempting eight violations and confirming all
eight were rejected. That is a check of the deployed schema, not a substitute
for the CI PostgreSQL run.

## Conventions

- Tests are named for the behaviour, not the method.
- Every test that asserts a security property says which requirement ID it
  covers, so the traceability matrix can be checked against reality.
- Data providers use PHPUnit attributes; `@dataProvider` annotations are not
  honoured in PHPUnit 12.
- No test is skipped, disabled, or loosened to get a green run.

## Planned

Vitest for the workout draft store, Playwright for the mobile end-to-end flow,
and the Stage 1 and Stage 2 acceptance suites in `docs/ba/acceptance-criteria.md`.
