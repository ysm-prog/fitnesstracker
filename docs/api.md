# API

All endpoints are under `/api/v1` and speak JSON.

## Authentication

Two modes, one guard. A first-party same-site client sends its `Origin`, is
recognised as stateful, and is served with a session cookie. A native client
supplies `device_name` at sign-in and receives a bearer token to send as
`Authorization: Bearer …`.

## Endpoints at Milestone 1

| Method | Path | Auth | Purpose |
| --- | --- | --- | --- |
| POST | `/api/v1/auth/register` | guest | Create an account; also creates the fitness profile |
| POST | `/api/v1/auth/login` | guest | Sign in; returns a token when `device_name` is given |
| POST | `/api/v1/auth/logout` | required | Sign out and revoke the current token |
| POST | `/api/v1/auth/forgot-password` | guest | Request a reset link |
| POST | `/api/v1/auth/reset-password` | guest | Set a new password; revokes all tokens |
| GET | `/api/v1/auth/email/verify/{id}/{hash}` | signed | Verify an address |
| POST | `/api/v1/auth/email/verification-notification` | required | Resend verification |
| GET | `/api/v1/profile` | required | Account and fitness profile |
| PATCH | `/api/v1/profile` | required | Update name, email, or password |
| DELETE | `/api/v1/account` | required | Delete the account and everything it owns |
| GET | `/api/v1/profile/fitness` | required | Fitness profile |
| PUT | `/api/v1/profile/fitness` | required | Update the fitness profile |

## Exercise library (Milestone 2)

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/api/v1/exercises` | The system library plus the caller's own. Paginated. Filters: `q`, `primary_muscle`, `equipment`, `include_archived`, `per_page` |
| POST | `/api/v1/exercises` | Create a custom exercise |
| GET | `/api/v1/exercises/{exercise}` | One exercise |
| PATCH | `/api/v1/exercises/{exercise}` | Update a custom exercise |
| DELETE | `/api/v1/exercises/{exercise}` | Delete if unused, archive if a program prescribes it. The response says which: `{"action": "deleted"}` or `{"action": "archived"}` |
| POST | `/api/v1/exercises/{exercise}/restore` | Un-archive |

System exercises have no owner. They are readable by everyone and writable by
nobody: a write attempt is **403**, with a message saying to create a variation.

## Programs (Milestone 2)

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/api/v1/programs` | The caller's programs with their prescriptions, in position order |
| POST | `/api/v1/programs` | Create a program |
| GET | `/api/v1/programs/{program}` | One program |
| PATCH | `/api/v1/programs/{program}` | Rename, re-describe, activate, or deactivate |
| DELETE | `/api/v1/programs/{program}` | Archive. Programs are never destroyed; history refers to them |
| POST | `/api/v1/programs/{program}/restore` | Un-archive |
| POST | `/api/v1/programs/{program}/duplicate` | Copy the program and its prescriptions. The copy starts inactive |
| POST | `/api/v1/programs/{program}/exercises` | Append a prescription; it takes the next position |
| PATCH | `/api/v1/programs/{program}/exercises/{templateExercise}` | Edit a prescription, or replace its exercise in place |
| DELETE | `/api/v1/programs/{program}/exercises/{templateExercise}` | Remove a prescription and close the gap |
| PUT | `/api/v1/programs/{program}/exercises/reorder` | Rewrite the order. Takes `template_exercise_ids`: the complete sequence, each exactly once |

Prescription validation, enforced in the Form Request and again as a PostgreSQL
check constraint: sets 1–20, reps 1–100, minimum ≤ maximum, RIR 0–5, rest
0–900 seconds. An exercise can only be prescribed if the caller can see it and
it is not archived — `exists` alone would let someone prescribe another user's
private exercise by guessing an identifier.

Planned for later milestones: sessions, daily metrics, weekly check-ins,
progress photos, personal records, targets, pain reports, and progress.

## Errors

Every failure has the same shape:

```json
{
  "error_code": "validation_failed",
  "message": "The submitted data is not valid.",
  "correlation_id": "0195f3c2-1c8a-7f4d-9b3e-2a5c6d7e8f90",
  "errors": { "height_cm": ["Height must be between 50 and 300 cm."] }
}
```

`errors` appears only where field-level detail exists. `correlation_id` is always
present and is echoed in the `X-Correlation-Id` response header.

| `error_code` | Status |
| --- | --- |
| `validation_failed` | 422 |
| `unauthenticated` | 401 |
| `not_found` | 404 |
| `forbidden` | 403 |
| `method_not_allowed` | 405 |
| `too_many_requests` | 429 |
| `server_error` | 500 |

An authorization failure on a resource owned by someone else returns **404, not
403**, so the API cannot be used to discover which records exist. A refusal on
something the caller *can* see but may not change — a system exercise — is a
genuine **403** with a reason, because its existence is not a secret. Policies
make the distinction with `Response::denyAsNotFound()` and `Response::deny()`.

Errors are mapped by HTTP status rather than by exception class. Laravel
collapses several exception types into a plain `HttpException` carrying only a
status, so matching on classes silently missed cases and returned 500.

## Rate limits

| Limiter | Applies to | Limit |
| --- | --- | --- |
| `auth` | register, login, verification | 5/min per credential, 20/min per address |
| `password-reset` | forgot, reset | 3/hour per credential, 10/hour per address |
| `api` | everything else | 120/min per user or address |

Limits are keyed on the credential as well as the address, so one shared network
cannot lock out an unrelated account and one address cannot spray attempts
across many accounts.

## Conventions

- Ownership is never accepted from the client. A `user_id` in a payload is
  stripped and ignored.
- Responses name their own top-level keys; there is no `data` wrapper.
- Sign-in gives the same answer for an unknown address and a wrong password.
- A password reset revokes every existing token.
