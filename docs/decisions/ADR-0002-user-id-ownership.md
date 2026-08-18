# ADR-0002: `user_id` ownership now, `tenant_id` deferred

- **Status:** Accepted
- **Date:** 2026-08-18

## Context

`docs/YSM-PROJECT-SCAFFOLD.md` requires `tenant_id uuid not null references
public.tenants(id)` on every business table. This product has exactly one user,
and its future expansion is to trainers, coaches, and gyms — a membership model,
not the tenant-per-organisation model the scaffold assumes.

## Decision

Every business table carries `user_id`, indexed, with a foreign key and
`ON DELETE CASCADE`. No `tenants` table is created.

## Consequences

**Gained.** The schema says what is true today. There is no tenant column that is
always the same value, no join that never filters anything, and no ambiguity
about which column owns a row.

**Lost.** Adding organisations later is a migration rather than a configuration
change. That migration is genuinely additive — a `tenants` table, a membership
table, a nullable `tenant_id` backfilled to a single default tenant, then made
non-null — and it is cheaper than carrying an unused column through ten
milestones of coaching logic.

**Trigger to revisit.** The first requirement for one person to see another
person's training data. At that point the ownership question changes from "whose
row is this" to "who may act on whose behalf", which needs a real design rather
than a column.
