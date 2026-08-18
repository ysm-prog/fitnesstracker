# Database

PostgreSQL 17, hosted on Supabase (`fitnesstracker-syd`, region `ap-southeast-2`).

## Two representations, one truth

Laravel migrations under `database/migrations/` are the source of truth. The SQL
actually applied to the hosted database is recorded as Supabase migrations, so
the deployed schema has a reviewable history. When the two disagree, the Laravel
migration wins and the difference is a bug to fix, not a choice.

## Tables at Milestone 1

| Table | Purpose | Key constraints |
| --- | --- | --- |
| `users` | Accounts | `users_email_unique` |
| `password_reset_tokens` | Reset flow | `email` primary key |
| `sessions` | Cookie sessions | indexed on `user_id`, `last_activity` |
| `personal_access_tokens` | Sanctum bearer tokens | `token` unique |
| `cache`, `cache_locks` | Cache store | `key` primary key |
| `jobs`, `job_batches`, `failed_jobs` | Queue | `jobs_queue_index` |
| `fitness_profiles` | One profile per user | `fitness_profiles_user_id_unique`, FK cascade, nine check constraints |

## Tables at Milestone 2

| Table | Purpose | Key constraints |
| --- | --- | --- |
| `exercises` | System and custom movements | `exercises_owner_name_unique` on `(user_id, lower(name)) NULLS NOT DISTINCT`; five check constraints |
| `workout_templates` | Programs | `workout_templates_owner_name_unique` on `(user_id, lower(name))` |
| `template_exercises` | Prescriptions within a program | `(workout_template_id, position)` unique; seven check constraints; `exercise_id` restricted on delete |

A null `user_id` on `exercises` means a system exercise. `NULLS NOT DISTINCT`
matters here: without it PostgreSQL treats each null owner as unique and the
shared library could hold two rows called "Back Squat".

`template_exercises.exercise_id` is **restricted**, not cascaded. Deleting an
exercise a program prescribes would silently change the program, so exercises
are archived instead — which is what the API does automatically.

## Conventions

- Ownership is `user_id`, indexed, foreign key, `ON DELETE CASCADE`. Deleting an
  account removes everything it owns.
- **Body weight is kilograms. Height is centimetres.** Always, regardless of the
  user's display preference. Storing display units is how a training log
  quietly corrupts a year of history.
- Check constraints back up Form Request validation so that an importer, a
  console command, or hand-written SQL cannot write a 4,000 kg body weight.
- Migrations are additive. A migration that may already have run is never edited.

## Check constraints are PostgreSQL-only

SQLite cannot add constraints to an existing table, so migrations add them only
when the driver is `pgsql`. It also has no way to say `NULLS NOT DISTINCT`, so
the system-library uniqueness rule exists only on PostgreSQL.

These constraints were verified on the deployed database by attempting eight
violations — a duplicate system name, an inverted rep range, 21 sets, 901
seconds of rest, two prescriptions at one position, deleting a referenced
exercise, an unknown loading type, and an `anon` read. All eight were rejected. The test suite's default SQLite run therefore
exercises application-layer validation alone. This is why `docs/testing.md`
requires a PostgreSQL run as well, and why the future concurrency suites cannot
be considered passed on SQLite.

## Planned schema

Milestones 2–10 add exercises, workout templates and template exercises, workout
sessions with immutable snapshots, sets, daily metrics, weekly check-ins,
progress photos, personal records, coaching summaries, exercise coaching
results, exercise targets, target overrides, and exercise pain reports. The
constraint design — including the partial unique index that enforces one active
target per exact scope, and the idempotency key on personal records — is set out
in `docs/ba/perplexity-review.md`.
