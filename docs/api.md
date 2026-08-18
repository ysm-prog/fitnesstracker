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

Planned for later milestones: exercises, programs, sessions, daily metrics,
weekly check-ins, progress photos, personal records, targets, pain reports, and
progress.

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
403**, so the API cannot be used to discover which records exist.

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
