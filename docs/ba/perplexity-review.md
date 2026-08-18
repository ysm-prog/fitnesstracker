# Repository Audit and Implementation Plan

Milestone 0. Performed 18 August 2026 against commit `01a806e`, before any
application code existed. Approved, and Milestone 1 built from it.

The full document, including the complete requirement traceability matrix, is
published at:
<https://claude.ai/code/artifact/5d55ffeb-f0b3-4df5-ab85-e2ce20e160c6>

This file records the findings and the plan that the repository still needs to
carry, so the record survives the link.

## Headline finding

There was nothing to audit in the brownfield sense. The repository held one
commit — a copy of the Enterprise Claude Code Framework — and 62 files, all
documentation, scripts, or licence. No `composer.json`, no `package.json`, no
`app/`, no `database/`, no `supabase/`, no SQL. Zero of the Stage 1 and Stage 2
criteria were met. This was a greenfield build with a governance layer attached.

## Live findings at audit time

| ID | Severity | Finding | Status |
| --- | --- | --- | --- |
| `SEC-001` | High | `.gitignore` did not ignore `.env`, `vendor/`, `node_modules/`, build output, or coverage | Fixed, Milestone 1 |
| `SEC-002` | Medium | No `.env.example`, so no declared configuration surface | Fixed, Milestone 1 |
| `SEC-003` | Low | CI ran only a documentation validator: no build, test, typecheck, lint, or audit | Fixed, Milestone 1 |
| `SEC-004` | Clean | No secrets committed; pattern scan across all tracked files returned no matches | Confirmed |
| — | Medium | `README.md` and `CHANGELOG.md` described the framework, not the product; no root `CLAUDE.md` existed | README and `CLAUDE.md` fixed; see note below |
| — | Medium | The framework validator scans every `*.md` and fails on unresolved `docs/` paths — a live trap for the documentation milestone | Hit immediately, fixed in `docs/decisions/ADR-0003-vendored-framework-validator.md` |

`CHANGELOG.md` is deliberately unchanged. The framework validator requires its
newest `## [x.y.z]` heading to match `.claude/VERSION`, so the file continues to
describe the vendored `.claude/` copy. Product history is the git log.

## Architecture decision

The brief specified Laravel, Sanctum, Policies, and Eloquent. The vendored
framework specified Supabase Auth, RLS as the authoritative boundary, and
`tenant_id` on every table. `.claude/CLAUDE.md` settles the precedence in the
brief's favour. Resolved as a Laravel monolith on Supabase PostgreSQL, recorded
in `docs/decisions/ADR-0001-laravel-authorization.md` and
`docs/decisions/ADR-0002-user-id-ownership.md`.

## Design obligations carried forward

The audit inverted the reliability and security sections, since defects cannot
be found in code that does not exist. Each category became an obligation with a
mechanism and an ID: duplicate writes to `UNIQUE (workout_exercise_id,
set_number)`; race conditions to `SELECT … FOR UPDATE` and conditional status
transitions; historical mutation to immutable snapshots; broken idempotency to
unique keys on `(session, engine_version)` and `(record_type, source_set_id)`.
The mechanisms are specified in `docs/deterministic-coaching-engine.md` and
`docs/database.md`.

## Traceability

Requirement IDs and their areas are listed in `docs/ba/requirements.md`.
Milestone 1 coverage is in `docs/ba/acceptance-criteria.md`. The complete matrix
of roughly 130 rows, covering every requirement across all thirteen milestones,
is in the published document linked above and is updated at the end of each
milestone.
