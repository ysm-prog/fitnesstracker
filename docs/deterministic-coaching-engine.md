# Deterministic Coaching Engine

**Status: specified, not implemented.** Milestones 7–10. This document is the
contract those milestones are built against.

Engine version: `deterministic_coach_v1`.

## Principles

1. Every result is computed by testable rules from recorded data. No model call.
2. Every summary and exercise result stores its `engine_version`. A rule change
   means a **new version**; stored history is never silently reinterpreted.
3. The engine is pure PHP with no Eloquent dependency. It takes plain data and
   returns plain results.
4. It never diagnoses an injury.

## Services

`VolumeCalculator`, `EstimatedOneRepMaxCalculator`, `PersonalRecordDetector`,
`AdherenceCalculator`, `ExerciseTrendAnalyzer`, `PlateauDetector`,
`ReadinessCalculator`, `ProgressionEngine`, `ExerciseTargetManager`,
`WorkoutCoachingEngine`.

## Calculations

**Estimated 1RM** — Epley: `weight × (1 + reps / 30)`. Eligible only when the
loading type is external weight, reps are 1–12, weight is above zero, and the
set is not a warm-up. Never computed for bodyweight, assisted, time, or distance
exercises.

**Volume** — `weight × reps` for external load. Session volume counts working and
backoff sets. Warm-up, drop, and failure volume are tracked separately so they
cannot inflate the headline figure.

**Unilateral convention** — each set records a `weight_basis` of `per_side` or
`combined`. Where the exercise is unilateral, the basis is `per_side`, and both
sides were performed, volume is `weight × reps × 2`. Tested both ways.

**Adherence** — `completed planned working sets ÷ planned working sets × 100`,
capped at 100. Optional work, warm-ups, and extra sets are excluded from both
numerator and denominator. Exercise-level adherence uses the same formula scoped
to one workout exercise.

**Primary working load** — the modal load across completed working sets, ties
broken by the heaviest. This is what makes `70×10, 70×10, 65×10` two successful
sets at 70 rather than three.

## Trend

At most five comparable sessions, ordered by workout date and completion time —
never by identifier. The current workout is excluded from its own baseline.
Signals: E1RM, same-load repetitions, sustained load, volume, adherence, RIR.

Statuses: `improving`, `stable`, `declining`, `inconsistent`, `insufficient_data`.

**One poor workout never produces a confirmed decline.**

## Plateau

At least four sufficiently comparable occurrences, from at most eight sessions.
Load PR, same-load repetition PR, E1RM PR, and volume PR are kept separate.
Same-load repetitions are compared only at the same normalised load. A one-off
heavier attempt is not sustained progression. Exercise-level adherence and long
training gaps are accounted for.

## Readiness

Pre-workout readiness and post-workout training state are separate calculations.
Pre-workout may use sleep, energy, soreness, recent fatigue, recent pain, and
training frequency. Post-workout may additionally use performance, trend,
fatigue, and post-workout energy.

Each returns score, status, confidence, inputs used, missing inputs, warnings,
and reason. Statuses: `good`, `moderate`, `low`, `very_low`, `insufficient_data`.
**Missing data reduces confidence** rather than being guessed.

## Progression

Actions: `increase_weight`, `increase_reps`, `maintain`, `reduce_weight`,
`reduce_sets`, `reduce_assistance`, `hold_for_recovery`, `hold_for_pain`,
`insufficient_data`, `manual_override`.

Priority order:

1. Relevant exercise pain
2. Serious recovery limitation supported by performance
3. Insufficient or invalid data
4. Weight progression
5. Rep progression
6. Maintain
7. Conservative reduction

**Increase weight only when** every planned primary working set was completed at
the intended load, all reached the top of the rep range, valid RIR is present and
meets the threshold, pain is absent, recovery is acceptable, and the loading type
supports external progression. Increase by the exercise's default increment only,
and never increase sets at the same time.

**Missing RIR** generally prevents an immediate load increase on one workout.
Prefer maintain or rep progression, with lower confidence.

**Below the top of the range** — keep the weight, aim for one or two more total
repetitions next time. Do not change the prescribed range automatically.

**Reduction** requires repeated underperformance, most primary sets below minimum
reps, excessive effort, and no sufficient alternative explanation, before a
conservative 5–10% cut. A single poor session is never enough.

**Readiness** alone never holds progression. A hold needs low readiness *plus*
decline, excessive effort, repeated poor recovery, or repeated deterioration.
Low sleep with excellent performance produces caution and reduced confidence.

**Bodyweight** movements track repetitions; the rep range is never changed
automatically. **Assisted** movements improving with lower assistance use
`reduce_assistance`, never `increase_weight`.

## Pain

Pain is scoped to the exercise where it was reported. `hold_for_pain` applies to
that exercise only. General session pain creates caution and does not mark every
exercise painful. Knee pain does not hold biceps curls.

## Targets

Scope is `user_id + workout_template_id + template_exercise_id + exercise_id`.
Resolution priority: exact-scope active manual override, exact-scope active
deterministic target, valid template target, template default. **Never resolved
by exercise ID alone** — the same exercise in two programs gets two targets.

Exactly one active target per exact scope, enforced by a partial unique index
with `NULLS NOT DISTINCT`. Activation is transactional: lock the current active
row, supersede it, insert the replacement, record audit information, commit.
Roll everything back on failure.

Lifecycle (`suggested`, `active`, `superseded`, `rejected`) is separate from
coaching state (`normal`, `pain_hold`, `recovery_hold`, `insufficient_data`).

`maintain` creates no meaningless duplicates. Holds retain the numeric
prescription while changing coaching state. `insufficient_data` never replaces a
valid active target. Manual overrides store previous and new values, reason,
timestamp, and actor, and never mutate the deterministic result.

Targets are compared semantically — action, state, caution code, reason category,
source, loading type — not only numerically.

## Analysis pipeline

```text
processing → PRs → volume → adherence → history → trend → plateau
→ post-workout state → progression → exercise results → target activation → complete
```

Statuses: `processing`, `complete`, `partial`, `failed`, each with error, start
time, and completion time. Partial target failure means `partial`. Engine failure
means `failed`. Neither is ever recorded as `complete`.

Idempotent for `(workout_session_id, engine_version)`. Reprocessing must not
duplicate summaries, exercise results, personal records, active targets, or
override history. **A completed workout stays completed even if analysis fails.**
