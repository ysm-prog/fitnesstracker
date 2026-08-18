# Requirements

Requirement IDs are the spine of this project: they appear in the traceability
matrix in `docs/ba/perplexity-review.md`, in acceptance criteria, and in test
docblocks. An ID means the same thing everywhere.

## Areas and ID ranges

| Prefix | Area | Milestone |
| --- | --- | --- |
| `AUTH-` | Registration, sign-in, sign-out, verification, password reset, deletion | 1 |
| `PROFILE-` | Fitness profile fields and ownership | 1 |
| `EX-` | Exercise library, loading types, immutability, archiving | 2 |
| `PROG-` | Templates, prescriptions, validation, historical immutability | 2–3 |
| `WORKOUT-` | Session lifecycle, snapshots, sets, numeric ranges | 3 |
| `SAVE-` | Debounced saving, save state, timer gating, idempotent completion | 3 |
| `METRIC-` | Daily metrics, rolling seven-day average, reliability | 4 |
| `CHECKIN-` | Weekly check-ins | 4 |
| `PHOTO-` | Progress photos, private storage, signed delivery | 4 |
| `PR-` | Personal records and their idempotency | 5 |
| `CALC-` | Volume, unilateral convention, E1RM, adherence | 7 |
| `ENGINE-` | The ten coaching services | 7–10 |
| `COACH-VER-` | Engine versioning | 7 |
| `COACH-TREND-` | Trend analysis | 8 |
| `COACH-PLATEAU-` | Plateau detection | 8 |
| `PAIN-` | Pain scoping | 9 |
| `READY-` | Readiness and post-workout state | 9 |
| `PROGR-` | Progression rules | 9 |
| `TARGET-` | Target scope, lifecycle, atomicity, overrides | 10 |
| `ANALYSIS-` | Pipeline, statuses, idempotency | 7–10 |
| `API-` | Surface, validation, policies, error envelope | 1–10 |
| `FE-` | Mobile-first views and the workout draft store | 2–11 |
| `EXPORT-` | JSON and CSV export, formula-injection protection | 12 |
| `SEC-` | Security controls | 1–13 |
| `DOC-` | Documentation | 1–12 |

## Delivered at Milestone 2 (front end)

`FE-003` exercise library and `FE-004` program management, plus the sign-in,
registration, and dashboard shell the rest of the interface hangs from. The
remaining `FE-` rows arrive with the milestones that give them something to
show.

## Delivered at Milestone 2

`EX-001` to `EX-009`, `PROG-001` to `PROG-007`, and the Milestone 2 portion of
`API-001`, `API-005` to `API-007`, `SEC-005` to `SEC-007`, and `SEC-012`.

`PROG-008` and `PROG-009` — editing a program never changing historical workout
data, and the prescription snapshot — belong to Milestone 3. There is nothing to
snapshot until workouts exist.

## Delivered at Milestone 1

`AUTH-001` to `AUTH-008`, `PROFILE-001` to `PROFILE-011`, `API-008`, `API-009`,
`SEC-001` to `SEC-003`, `SEC-005` to `SEC-007`, `SEC-010` to `SEC-012`, and the
Milestone 1 portion of `DOC-001` to `DOC-015`.

Each has at least one test naming it. The full matrix, including everything not
yet delivered, is in `docs/ba/perplexity-review.md`.
