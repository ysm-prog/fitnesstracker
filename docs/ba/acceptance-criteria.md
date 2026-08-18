# Acceptance Criteria

## Milestone 1 — met

| Criterion | Verified by |
| --- | --- |
| A visitor can register and receives a fitness profile | `RegistrationTest` |
| Duplicate address rejected with the standard envelope | `RegistrationTest` |
| Sign-in works and is case-insensitive on the address | `LoginTest` |
| Unknown address and wrong password are indistinguishable | `LoginTest` |
| A named device receives a bearer token | `LoginTest` |
| Repeated failures are throttled | `LoginTest` |
| Sign-out ends the session and revokes the current token | `LogoutTest` |
| Reset link sent; unknown address gets the same response | `PasswordResetTest` |
| Reset works and revokes every existing token | `PasswordResetTest` |
| Signed verification link works; unsigned and mismatched are refused | `EmailVerificationTest` |
| An unverified user still has full access | `EmailVerificationTest` |
| Profile reads, updates, and validates all ranges | `FitnessProfileTest` |
| A client-supplied `user_id` cannot touch another user's profile | `FitnessProfileTest` |
| Account deletion removes the account and everything it owns | `AccountTest` |
| Deletion requires the current password | `AccountTest` |
| Policies deny another user's records | `AuthorizationTest` |
| Every protected route refuses an anonymous caller | `AuthorizationTest` |
| Deletion only ever removes the caller | `AuthorizationTest` |
| Error envelope on 404, 405, 422, 429, 500 | `ErrorEnvelopeTest` |
| Correlation ID echoed when valid, replaced when not | `ErrorEnvelopeTest`, `CorrelationIdTest` |
| An unexpected failure leaks no SQL, host, password, class, or trace | `ErrorEnvelopeTest` |
| Formatting clean | `pint --test` |
| No dependency advisories | `composer audit` |

63 tests, 226 assertions, passing.

## Stage 1 — not started

Duplicate set protection; debounced saving; immediate save on set completion;
retry and failed-save visibility; unsaved-data completion blocking; idempotent
completion; numeric validation; correct daily metrics and seven-day average;
correct and idempotent personal records; editable programs with immutable
historical snapshots; authorization and IDOR protection across every resource.

**Milestone 6 is a hard gate.** No Stage 2 work begins until all of the above
pass.

## Stage 2 — not started

Volume, unilateral volume, E1RM, workout and exercise adherence; comparable trend
analysis with no false decline from one poor workout; four-occurrence plateau
rule; same-load rep plateau; sustained-load logic; PR types kept separate; scoped
pain handling; missing-data confidence reduction; no automatic hold from low
readiness alone; missing-RIR protection; mixed-load handling; stable bodyweight
rep range; assisted-movement `reduce_assistance`; conservative reduction
requirements; exact-scope targets; one active target per scope; atomic
activation; insufficient-data target preservation; holds preserving numeric
prescriptions; manual overrides with immutable history; analysis statuses;
recalculation idempotency; authorization; passing test, lint, typecheck, and
build commands.

## Required scenarios — not started

`10,10,10` with valid RIR increases weight. `10,9,8` maintains or increases reps.
Top reps without RIR gives no immediate load increase. `70×10, 70×10, 65×10` is
not three successful main sets. One poor workout causes no reduction. Repeated
below-range reps plus excessive effort may cause a conservative reduction. Low
sleep with excellent performance is caution, not a hold. Low readiness plus
decline is a recovery hold. Exercise-specific pain holds only that exercise. Knee
pain does not hold curls. Bodyweight at top reps preserves the rep range. Assisted
pull-ups reduce assistance. Insufficient data preserves the current target. Four
comparable sessions are needed for a plateau. Changing loads are not compared
directly for a rep plateau. Long training gaps are accounted for. Duplicate sets
are rejected. Completion, analysis, and PR creation are idempotent. Target
activation is atomic. The same exercise in two programs gets different targets.
A manual override preserves historical results. User A is denied User B's data.
