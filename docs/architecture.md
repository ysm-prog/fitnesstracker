# Architecture

This describes the system as it is today, at the end of Milestone 1, not an
aspirational design. Anything not yet built is marked as such.

## Shape

One deployable: a Laravel application serving a JSON API under `/api/v1`,
backed by PostgreSQL hosted on Supabase.

```text
HTTP route
    ↓
Form Request          authoritative validation
    ↓
Controller            thin; no business rules
    ↓
Policy                authorization, per user-owned model
    ↓
Application service   orchestration and transaction boundaries
    ↓
Domain service        pure calculation, no ORM  (Milestone 7 onward)
    ↓
Eloquent model
    ↓
PostgreSQL
```

Dependency direction runs downward only. A domain service never reaches back
into HTTP or Eloquent; that is what lets the coaching rules be tested as plain
functions instead of through database fixtures.

## Modules today

| Path | Owns |
| --- | --- |
| `app/Http/Controllers/Auth/` | Registration, sign-in, sign-out, password reset, email verification |
| `app/Http/Controllers/` | Account, fitness profile, exercises, programs, prescriptions |
| `app/Http/Requests/` | All input validation |
| `app/Http/Resources/` | Response shaping; nothing internal leaks |
| `app/Policies/` | Ownership checks |
| `app/Models/` | `User`, `FitnessProfile`, `Exercise`, `WorkoutTemplate`, `TemplateExercise` |
| `app/Services/` | `ProgramExerciseSequencer`, `ProgramDuplicator` |
| `app/Enums/` | Training level, primary goal, units, loading type, muscle group, equipment |
| `app/Support/` | Correlation IDs and the API error envelope |
| `app/Providers/` | Rate limits, response conventions |

## Not built yet

`app/Domain/Coaching/` (the ten engines), workout execution, metrics, photos,
records, targets, and the front end. The sequence and the reasoning are in
`docs/ba/perplexity-review.md`.

## Cross-cutting decisions

**Correlation IDs** are assigned by global middleware, so a request that matches
no route still gets one. A caller may supply `X-Correlation-Id`, but only if it
is a well-formed UUID; anything else is replaced rather than reflected.

**Errors** all leave through one builder, `App\Support\ApiError`. That is what
makes "never expose SQL, stack traces, secrets, or connection strings" a
property of the system rather than a habit each handler has to remember.

**Responses** name their own top-level keys (`user`, `fitness_profile`). Laravel's
`data` wrapper is disabled, so clients walk one level fewer.

**Ordering is owned by one class.** `position` on a template exercise is not
fillable and is written only by `ProgramExerciseSequencer`. Reordering rewrites
the whole sequence in two passes — every row is moved out of the way first —
because assigning final positions one row at a time collides with the unique
index the moment two exercises swap.

**Refusals distinguish "not yours" from "not allowed".** A system exercise is
not a secret, so editing one is a 403 with a reason. Another user's row is a
secret, so every refusal there is a 404 — a 403 would confirm the identifier
exists. Policies express this with `Response::deny()` and
`Response::denyAsNotFound()` rather than the controller guessing.

**Analysis will not share a transaction with completion** (Milestone 7). A
completed workout must stay completed even when analysis fails, so completion
commits first and analysis is dispatched afterwards on the database queue.

## Divergences from the YSM scaffold

`docs/YSM-PROJECT-SCAFFOLD.md`, vendored with the Claude framework, specifies
Supabase Auth, RLS as the authorization boundary, and `tenant_id` on every
business table. This project uses Laravel authentication, Laravel policies, and
`user_id`. Both divergences are deliberate and recorded:

- `docs/decisions/ADR-0001-laravel-authorization.md`
- `docs/decisions/ADR-0002-user-id-ownership.md`
- `docs/decisions/ADR-0003-vendored-framework-validator.md`
- `docs/decisions/ADR-0004-postgrest-exposure.md`
