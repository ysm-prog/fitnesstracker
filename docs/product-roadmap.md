# Product Roadmap

## Milestones

| # | Milestone | Status |
| --- | --- | --- |
| 0 | Audit and architecture | Complete — `docs/ba/perplexity-review.md` |
| 1 | Authentication and users | **Complete** |
| 2 | Exercises and programs | Not started |
| 3 | Reliable workout execution | Not started |
| 4 | Metrics, check-ins, photos | Not started |
| 5 | Progress and personal records | Not started |
| 6 | Stage 1 reliability acceptance | Not started — hard gate |
| 7 | Deterministic calculations | Not started |
| 8 | Trends and plateau | Not started |
| 9 | Readiness and progression | Not started |
| 10 | Target lifecycle and manual override | Not started |
| 11 | Training Analysis UI | Not started |
| 12 | Security, testing, QA | Not started |
| 13 | Staging and deployment | Not started |

Milestone 6 is a hard gate. No Stage 2 work begins until every Stage 1 criterion
passes.

## Future scope, deliberately not built

### Nutrition

Documented as future scope. Not implemented in this build. The fitness profile
carries a `dietary_preference` placeholder so onboarding can capture the
preference without a later table rewrite; nothing reads it.

### Multi-user

Trainers, coaches, gyms, and sports professionals. The path is an additive
`tenant_id` migration on top of the existing `user_id` ownership, plus a
membership model — see `docs/decisions/ADR-0002-user-id-ownership.md`. It is not
designed speculatively now.

### Generative AI

Not until the deterministic engine has passed its acceptance criteria. When it
arrives, the flow is fixed:

```text
validated workout data → deterministic engine → structured context
→ AI explanation → validated proposal → user approval → application service
```

Four interfaces are planned. **Signatures only** — no implementation:

```php
buildWorkoutCoachContext(WorkoutSession $session): array;
buildWeeklyCoachContext(User $user, CarbonInterface $weekStart): array;
proposeWorkoutAdjustment(array $context): AdjustmentProposal;
proposeExerciseSubstitution(array $context): SubstitutionProposal;
```

Boundaries that do not move:

- AI never executes SQL and never mutates workout records directly.
- AI output is validated as structured data before it is used for anything.
- A proposal requires user approval before an application service applies it.
- The deterministic engine remains authoritative; AI explains and suggests.
- Model failure has defined fallback behaviour: the deterministic result stands
  on its own, without explanation.
