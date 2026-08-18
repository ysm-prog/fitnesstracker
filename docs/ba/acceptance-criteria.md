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

63 tests, 226 assertions at the time; 144 tests and 528 assertions now.

## Milestone 2 — met

| Criterion | Verified by |
| --- | --- |
| The library shows system exercises plus the caller's own, never anyone else's | `ExerciseLibraryTest` |
| Archived exercises are hidden unless asked for | `ExerciseLibraryTest` |
| Search and filters work; a wildcard in the term is literal text | `ExerciseLibraryTest` |
| The library is paginated | `ExerciseLibraryTest` |
| A custom exercise can be created, named uniquely per owner, and updated | `ExerciseManagementTest` |
| A custom exercise may reuse a system name as a variant | `ExerciseManagementTest` |
| Incoherent loading combinations are rejected, including on partial updates | `ExerciseManagementTest` |
| Invalid muscle, equipment, loading type, increment, and rest are rejected | `ExerciseManagementTest` |
| An unused exercise is deleted; a prescribed one is archived instead | `ExerciseManagementTest` |
| Archived exercises can be restored; restoring a live one is a conflict | `ExerciseManagementTest` |
| System exercises are readable by all and writable by none | `SystemExerciseTest` |
| The system library seeder is idempotent and classifies loading correctly | `SystemExerciseTest` |
| Loading-type semantics for assisted and bodyweight movements are pinned | `LoadingTypeTest` |
| Programs can be created, renamed, activated, deactivated, archived, restored | `ProgramManagementTest` |
| Duplication copies prescriptions, starts inactive, and stays independent | `ProgramManagementTest` |
| Prescriptions can be added, edited, replaced in place, and removed | `PrescriptionTest` |
| Sets 1–20, reps 1–100, min ≤ max, RIR 0–5, rest 0–900 are enforced | `PrescriptionTest` |
| A partial edit cannot invert the rep range | `PrescriptionTest` |
| Another user's or an archived exercise cannot be prescribed | `PrescriptionTest` |
| Reordering rewrites the sequence; adjacent swaps do not collide | `ProgramOrderingTest` |
| Reordering requires the complete sequence, each item exactly once | `ProgramOrderingTest` |
| Removing an exercise compacts positions; the next append continues cleanly | `ProgramOrderingTest` |
| User A cannot read, change, or destroy any of User B's exercises, programs, or prescriptions | `LibraryAuthorizationTest` |
| A client-supplied owner is ignored on creation | `LibraryAuthorizationTest` |
| Every library route refuses an anonymous caller | `LibraryAuthorizationTest` |

### Front end (Milestone 2)

| Criterion | Verified by |
| --- | --- |
| A visitor can register and land on the dashboard | `e2e/walkthrough.spec.ts` |
| The shared library renders with all 26 exercises | `e2e/walkthrough.spec.ts` |
| Search narrows the library, and how a movement progresses is on the card | `e2e/walkthrough.spec.ts` |
| A program can be created and prescriptions added, in order | `e2e/walkthrough.spec.ts` |
| The API's own validation message reaches the screen | `e2e/walkthrough.spec.ts` |
| Credentials travel cross-origin; the CSRF cookie is fetched for unsafe requests only and echoed url-decoded | `src/lib/__tests__/api.test.ts` |
| The error envelope becomes a typed error with field messages; a 401 is distinguishable; a non-JSON body is refused | `src/lib/__tests__/api.test.ts` |
| Client-side ranges mirror the Form Requests, including min ≤ max and form-string coercion | `src/lib/__tests__/schemas.test.ts` |

18 front-end tests and 2 browser walkthroughs, run at a Pixel 7 viewport.

Verified directly against the deployed PostgreSQL database: duplicate system
name, inverted rep range, 21 sets, 901 seconds of rest, duplicate position,
deleting a referenced exercise, unknown loading type, and an `anon` read — all
eight rejected.

## Stage 1 — not started

Duplicate set protection; debounced saving; immediate save on set completion;
retry and failed-save visibility; unsaved-data completion blocking; idempotent
completion; numeric validation; correct daily metrics and seven-day average;
correct and idempotent personal records; immutable historical snapshots.

Editable programs and IDOR protection for the library are done (Milestone 2).
The snapshot half of "editable programs, immutable history" arrives with
Milestone 3, since there is nothing to snapshot until workouts exist.

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
