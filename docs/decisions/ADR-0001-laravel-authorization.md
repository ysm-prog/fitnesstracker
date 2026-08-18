# ADR-0001: Authorization in Laravel policies, not Row Level Security

- **Status:** Accepted
- **Date:** 2026-08-18

## Context

The implementation brief specifies Laravel, Sanctum, Form Requests, and Policies,
with authorization enforced server-side. The Claude framework vendored into this
repository — `.claude/CLAUDE.md` and `docs/YSM-PROJECT-SCAFFOLD.md` — specifies
Supabase Auth and states that "RLS is the authoritative tenant isolation
boundary". Both are checked in. They cannot both be followed.

`.claude/CLAUDE.md` settles precedence itself: "Where a standard and a
project-specific instruction conflict, the project instruction wins; say so
explicitly rather than applying the standard silently." The scaffold permits
divergence driven by a concrete requirement, recorded in an ADR. This is that
record.

## Decision

Authorization is enforced in Laravel policies. The application connects to
PostgreSQL as a single owning role. RLS is not the authorization boundary.

Supabase remains the platform: PostgreSQL for data, and Supabase Storage over
its S3-compatible endpoint for progress photos from Milestone 4.

## Consequences

**Gained.** The brief is delivered exactly. Authorization sits next to the
business rules that define it, in code that is unit-testable without a database
role per test. The coaching engine — server-side PHP running under one identity —
does not have to negotiate row visibility with the database while it computes.

**Lost.** No defence in depth at the database. A missing policy check is a real
exposure rather than something RLS would have caught. Mitigations, which are not
optional:

- A policy for every user-owned model, checked at the controller boundary.
- Paired IDOR tests on every endpoint, proving User A is denied User B's rows.
- Ownership derived from the session, never the payload, with a test for it.

**Deferred.** If real multi-tenancy arrives — trainers with clients, gyms with
members — RLS should be reconsidered as a second layer. The deny-by-default
posture in `docs/decisions/ADR-0004-postgrest-exposure.md` is already the base
that policies would be added on top of.
