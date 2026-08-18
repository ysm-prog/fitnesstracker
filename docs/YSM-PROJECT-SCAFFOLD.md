# YSM Project Scaffold

## Purpose

The Enterprise Claude Code Framework is the reusable engineering layer for YSM projects. This document defines the standard application and database baseline that new YSM products should follow.

The standard is intentionally a **reference architecture**, not a copy of YSM-Hub. New projects should start clean and add only the domain they actually need.

## Reference implementation

**YSM-Hub** (`ysm-prog/ysm-hub`) is the reference implementation for the database and multi-tenant application conventions.

Observed YSM-Hub conventions include:

- React + Vite application hosted on Vercel.
- Supabase PostgreSQL, Auth, Storage and Realtime.
- Serverless API functions where backend endpoints are needed.
- A shared `lib/` layer for technical helpers and integration concerns.
- Canonical `supabase/schema.sql` plus timestamped migrations.
- Database tests for RLS and security-sensitive database behaviour.
- Tenant isolation using `tenant_id` and PostgreSQL RLS.
- Project-specific architecture and operational rules recorded in `CLAUDE.md`.

The exact frontend framework may vary by product. The architectural constraints below should remain stable unless an ADR records why they do not.

## Default application architecture

### 1. Prefer one deployable

Start with a single application/deployable. UI, API/route handlers and application services can live in the same repository and deployment.

Do not introduce separate API, worker, queue, or microservice infrastructure merely because it is fashionable or theoretically scalable. Split a component only when an actual requirement—such as independent scaling, isolation, execution time, or operational ownership—requires it.

### 2. Clear dependency direction

Preferred dependency direction:

```text
UI / API entry points
        ↓
Application / domain services
        ↓
Repositories / integration adapters
        ↓
Supabase / external systems
```

Business rules belong in domain/application services, not in UI components or HTTP handlers.

External providers should be accessed through explicit integration adapters. This keeps vendor-specific details out of core business logic and makes testing easier.

### 3. Modular monolith first

Organise the code into meaningful domain modules. Prefer a modular monolith with explicit boundaries over an early microservice architecture.

A domain module should make it obvious:

- what data it owns;
- which business rules it enforces;
- which services/use cases it exposes;
- which external integrations it depends on;
- which permissions govern it.

### 4. Keep infrastructure replaceable where practical

Do not create abstract interfaces for every function. Add a boundary when it materially improves testing, security, changeability, or separation of concerns.

## Standard repository skeleton

```text
<project>/
├── .claude/
│   ├── CLAUDE.md
│   ├── VERSION
│   ├── agents/
│   ├── skills/
│   ├── standards/
│   ├── workflows/
│   └── templates/
├── src/
│   ├── app/                    # framework entry points/routes
│   ├── components/             # UI components
│   ├── domain/                 # domain rules/models
│   ├── services/               # application use cases
│   ├── lib/                    # shared technical helpers
│   └── integrations/           # external providers
├── supabase/
│   ├── schema.sql              # canonical idempotent schema
│   ├── migrations/              # exact as-applied migrations
│   └── tests/                  # pgTAP / DB security tests
├── docs/
│   ├── decisions/              # ADRs
│   ├── architecture.md
│   └── tech-debt.md
├── scripts/
├── tests/
├── .env.example
├── CLAUDE.md
├── README.md
└── package.json / pyproject.toml / <project manifest>
```

Projects may adapt the folder names to the selected framework. Do not create layers that have no current use.

## Standard database architecture

### Supabase PostgreSQL

Supabase PostgreSQL is the default YSM database platform.

Use Supabase Auth for identity, Postgres for transactional data, Storage for files, and Realtime only where the feature needs event-driven client updates.

### Tenant model

The standard model is:

```text
tenants
  └── tenant-owned business tables
         └── tenant_id → tenants.id
```

Every tenant-owned business table must have:

```sql
tenant_id uuid not null references public.tenants(id)
```

Use foreign keys and suitable indexes. `tenant_id` should normally be indexed because it participates in both RLS evaluation and tenant-scoped queries.

### Row Level Security

RLS is the authoritative tenant isolation boundary.

At minimum, projects should provide central helper functions equivalent to:

```sql
current_user_tenant_id()
is_platform_admin()
is_admin_or_manager()
```

A normal tenant-scoped policy should conceptually enforce:

```sql
is_platform_admin() or tenant_id = current_user_tenant_id()
```

Projects may add shop/team/user-level restrictions on top of tenant isolation.

A client-provided tenant identifier must never be sufficient to cross the tenant boundary.

### Privileged operations

Service-role/backend operations may perform cross-tenant or administrative work only after the server has explicitly checked the caller's authorization.

Security-definer functions must:

- have a narrow purpose;
- use a safe `search_path` strategy;
- validate authorization explicitly;
- have automated tests;
- avoid becoming a generic bypass around RLS.

### Canonical schema + migrations

YSM projects maintain two complementary representations:

```text
supabase/schema.sql
    ↓
canonical, idempotent, reproducible database definition

supabase/migrations/*.sql
    ↓
exact SQL changes applied over time, for review and history
```

The canonical schema is what CI/bootstrap should use to create a database from scratch. Migration files are the historical record of changes actually applied.

Both representations must be updated together when the schema changes.

### Database testing

At minimum, cover:

- cross-tenant reads are blocked;
- cross-tenant writes are blocked;
- authorised tenant access works;
- role-specific restrictions work;
- platform/admin operations are correctly restricted;
- security-definer functions cannot be abused;
- important unique/foreign-key/constraint invariants hold.

Use mutation-style tests for RLS and other security boundaries where practical.

## Documentation contract

Every new YSM project should contain:

### `CLAUDE.md`

Project-specific source of truth for:

- product purpose;
- domain terminology;
- users/roles;
- architecture;
- database conventions;
- integrations;
- business rules;
- commands;
- current priorities;
- known constraints.

### `<project>/docs/decisions/`

Use ADRs for significant architectural decisions, particularly when the project deliberately diverges from this scaffold.

### `<project>/docs/architecture.md`

Describe the current system rather than an aspirational design. Include major modules, dependency direction, data ownership, authentication/authorization and external integrations.

### `<project>/docs/tech-debt.md`

Record known compromises, why they exist, impact, and what would trigger remediation.

## New project bootstrap

A new YSM project should follow this sequence:

1. Create the application repository.
2. Install the generic Enterprise Claude framework into `.claude/` without overwriting project files.
3. Add the YSM project scaffold template to the root `CLAUDE.md` and tailor it to the product.
4. Establish the initial architecture and ADR directory.
5. Create the initial Supabase schema with tenants, profiles/auth linkage, and the minimum required domain tables.
6. Enable and test RLS before adding broad application functionality.
7. Add CI gates for build, tests, database schema and RLS/security checks.
8. Build domain modules incrementally.

## Explicit non-goals

The scaffold does **not** require:

- copying all YSM-Hub tables;
- copying YSM-Hub UI/components;
- adopting every YSM-Hub integration;
- introducing microservices;
- introducing a queue/job platform before a requirement exists;
- adding abstractions for speculative future providers;
- reproducing YSM-Hub's historical technical debt.

## Decision rule

When a new project diverges from this scaffold, ask:

> Is the divergence driven by a concrete product, security, performance, operational, or regulatory requirement?

If yes, document the decision in an ADR. If not, follow the scaffold.
