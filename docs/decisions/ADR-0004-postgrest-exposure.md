# ADR-0004: Closing the PostgREST door on application tables

- **Status:** Accepted
- **Date:** 2026-08-18

## Context

Discovered while deploying the Milestone 1 schema, not during design.

Supabase publishes the `public` schema through PostgREST and grants the `anon`
and `authenticated` roles table privileges by default. That is the right default
for an application whose authorization boundary *is* RLS. This application's is
not — see `docs/decisions/ADR-0001-laravel-authorization.md` — so left alone,
anyone holding the project's publishable anon key could have read every user's
body weight and training history over HTTPS, entirely bypassing Laravel.

## Decision

Two independent locks on every table, because either alone is a single point of
failure:

1. RLS **enabled and forced** with **no policies**. No role that does not bypass
   RLS can see a row. The application connects as `postgres`, which has
   `BYPASSRLS`, so it is unaffected.
2. All privileges revoked from `anon` and `authenticated`, and default privileges
   revoked in the schema so tables added later do not silently inherit them.

## Consequences

Verified rather than assumed: connecting as `anon` and reading `public.users`
returns `permission denied for table users`, while the application role reads
normally. `FORCE ROW LEVEL SECURITY` was checked specifically because it applies
RLS to the table owner as well — `postgres` carries `BYPASSRLS`, so it does not
lock the application out, but a future role change could.

Supabase's linter reports `rls_enabled_no_policy` at INFO level on every table.
Under this design that is the intended state and not a finding. If RLS is ever
adopted as the real boundary, these become the deny-by-default base that policies
are added on top of.

**Every future migration must apply the same treatment to the tables it creates.**
The revoked default privileges cover the grants; RLS still has to be enabled per
table.
