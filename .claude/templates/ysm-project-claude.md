# <Project Name>

## Purpose
<What the product does and why it exists>

## Users
- <Role>

## Technology
- Application: <framework/runtime>
- Database: Supabase PostgreSQL
- Authentication: Supabase Auth
- Storage: Supabase Storage where required
- Hosting: <platform>
- AI: <provider/models or N/A>
- External integrations: <list>

## Architecture

Follow the Enterprise Claude Code Framework and use the YSM-Hub reference architecture as the default application baseline.

### Default architectural shape

- One deployable application by default. Do not introduce a separate API service, worker service, queue, or microservice unless requirements justify the operational complexity.
- Keep UI/API concerns separate from domain/business logic.
- UI and API/route handlers call shared domain/application services rather than embedding business rules in controllers or components.
- Keep infrastructure adapters behind clear boundaries.
- Keep data access explicit and reviewable; do not allow arbitrary persistence logic to leak across the application.
- Prefer a modular monolith with clear domain boundaries before considering service decomposition.

### Suggested layout

```text
<project>/
├── .claude/
├── docs/
│   ├── decisions/
│   ├── architecture.md
│   └── tech-debt.md
├── src/
│   ├── app/ or routes/          # UI + API entry points
│   ├── components/              # reusable UI
│   ├── domain/                  # domain models and rules
│   ├── services/                # application/use-case services
│   ├── lib/                     # shared technical helpers
│   └── integrations/            # external system adapters
├── supabase/
│   ├── schema.sql               # canonical, idempotent database schema
│   ├── migrations/              # timestamped as-applied changes
│   └── tests/                   # pgTAP / database security tests
├── scripts/
├── tests/
├── .env.example
├── CLAUDE.md
└── README.md
```

Adjust this layout to the selected frontend/backend framework. Do not create empty directories that are not needed.

## Database standard — YSM-Hub compatible

YSM-Hub is the reference implementation for database tenancy, RLS, migrations, and database testing. Reuse the conventions, not YSM-Hub's business schema.

### Supabase/Postgres

- Use Supabase PostgreSQL as the default database for YSM applications.
- Use Supabase Auth for application authentication unless a documented requirement says otherwise.
- Use Supabase Storage for user/application files where appropriate.
- Use database constraints, indexes, foreign keys, and functions to enforce invariants that belong in the database.

### Multi-tenancy

Any table containing tenant-owned business data MUST include:

```sql
tenant_id uuid not null references public.tenants(id)
```

Tenant isolation MUST be enforced with Row Level Security (RLS), not only application-side filters.

Use a small set of well-tested helper functions for the current tenant and privileged roles. A typical model is:

- `current_user_tenant_id()`
- `is_admin_or_manager()`
- `is_platform_admin()`

Adapt names only when a project has a stronger existing convention.

The standard select boundary is conceptually:

```sql
is_platform_admin() or tenant_id = current_user_tenant_id()
```

Never trust a client-supplied `tenant_id` as an authorization boundary.

### RLS requirements

- Enable RLS on tenant-owned tables.
- Add explicit policies for SELECT/INSERT/UPDATE/DELETE as required.
- Test cross-tenant isolation.
- Test privileged/security-definer functions.
- When a security-sensitive column must not be self-editable, enforce that restriction with a trigger or controlled server-side operation as appropriate; do not rely on an overly broad self-update policy alone.

### Schema and migrations

Maintain BOTH:

1. `supabase/schema.sql` — the canonical, idempotent schema that can be recreated safely for CI/bootstrap.
2. `supabase/migrations/<UTC timestamp>_<description>.sql` — the exact SQL applied for each schema change.

A schema change is not complete until both are updated.

Prefer forward, additive migrations. Destructive changes require an ADR, impact assessment, and a safe transition plan where practical.

### Database tests

Any new or changed RLS policy, privileged function, tenant isolation rule, or security-definer function needs a database test.

The preferred pattern is mutation-style testing: prove that the test fails when the protection is removed, then passes with the protection restored.

## Business rules
- <Important project-specific rule>

## Current priorities
- <Priority>

## Constraints
- <Constraint>

## Important integrations
- <Integration>

## Security requirements
- Treat tenant isolation as a hard security boundary.
- Never commit secrets.
- Validate and authorize server-side operations.
- Use least-privilege access for service credentials.
- Review authentication, authorization, RLS, uploads, webhooks, and external integrations before release.

## Quality gates

Before substantial work is considered complete, run the relevant checks for the project:

- type checking
- linting
- unit tests
- integration/E2E tests
- database schema/RLS tests
- production build
- security review for sensitive changes

## Development commands
- Install: `<command>`
- Dev: `<command>`
- Test: `<command>`
- Lint: `<command>`
- Type check: `<command>`
- Build: `<command>`
- Database tests: `<command>`

## Project-specific instructions
<Rules that apply to this repository only>
