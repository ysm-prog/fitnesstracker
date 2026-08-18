# Framework documentation

- `README.md` at the repository root — what the framework is and how to validate a change to it.
- `docs/USING-WITH-CLAUDE-CODE.md` — bootstrapping a project, keeping project and framework context separate, updating a project's copy, and the distinction between skills and agents.
- `docs/YSM-PROJECT-SCAFFOLD.md` — standard application architecture and YSM-Hub-compatible database conventions for new YSM products.
- `CHANGELOG.md` at the repository root — what changed in each framework release.
- `scripts/install-into.sh` — installs `.claude/` into a project without overwriting anything already there.
- `scripts/validate-framework.py` — the structural checks CI runs on this repository.

## Catalogue

Everything a Claude Code session can draw on. The entry point is `.claude/CLAUDE.md`, which maps work to the standards and workflows below.

### Standards

Binding rules for their area. Read the relevant one before reviewing or changing code it covers.

| Standard | Covers |
| --- | --- |
| `.claude/standards/architecture.md` | module boundaries, dependency direction, integration design |
| `.claude/standards/coding.md` | writing and reviewing application code |
| `.claude/standards/review.md` | conducting any review: evidence, verification, severity |
| `.claude/standards/security.md` | authorization, untrusted input, secrets, sensitive data |
| `.claude/standards/testing.md` | tests and regression coverage |
| `.claude/standards/database.md` | schemas, queries, indexes, migrations, retention |
| `.claude/standards/performance.md` | latency, throughput, payload size, caching, behaviour at scale |
| `.claude/standards/operations.md` | builds, configuration, deployment, monitoring, backups, on-call |
| `.claude/standards/ux.md` | user-facing interfaces, workflows, accessibility |
| `.claude/standards/ai.md` | prompts, model calls, retrieval, LLM-backed behaviour |

### Workflows

Step sequences for recognisable kinds of work.

| Workflow | Use for |
| --- | --- |
| `.claude/workflows/new-feature.md` | building a new capability |
| `.claude/workflows/bug-fix.md` | diagnosing and correcting a defect |
| `.claude/workflows/architecture-review.md` | assessing structure and proposing a target state |
| `.claude/workflows/refactoring.md` | behaviour-preserving structural improvement |
| `.claude/workflows/dependency-upgrade.md` | moving a dependency to a new version |
| `.claude/workflows/security-review.md` | reviewing a change or area for security risk |
| `.claude/workflows/release.md` | preparing and verifying a release |
| `.claude/workflows/incident-response.md` | responding to a live production failure |

### Commands

One entry point per workflow, invoked as `/name`. Each is a thin starter that reads its
workflow and follows it; the workflow file remains the definition. They set
`disable-model-invocation: true`, so they cost no context until invoked and Claude does
not start one on its own — `.claude/CLAUDE.md` already directs it to the workflow file.

They live under `.claude/skills/` rather than in a commands directory because custom
commands have been merged into skills, and a project skill takes precedence over both a
command and a bundled skill of the same name.

| Command | Runs |
| --- | --- |
| `/new-feature` | `.claude/skills/new-feature/SKILL.md` |
| `/bug-fix` | `.claude/skills/bug-fix/SKILL.md` |
| `/architecture-review` | `.claude/skills/architecture-review/SKILL.md` |
| `/refactoring` | `.claude/skills/refactoring/SKILL.md` |
| `/dependency-upgrade` | `.claude/skills/dependency-upgrade/SKILL.md` |
| `/security-review` | `.claude/skills/security-review/SKILL.md` |
| `/release` | `.claude/skills/release/SKILL.md` |
| `/incident-response` | `.claude/skills/incident-response/SKILL.md` |

### Skills

Specialist expertise, applied selectively rather than all at once.

| Skill | Expertise |
| --- | --- |
| `.claude/skills/principal-architect/SKILL.md` | technical ownership and prioritisation of cross-cutting change |
| `.claude/skills/solution-architect/SKILL.md` | system boundaries, dependency direction, integration contracts |
| `.claude/skills/backend-architect/SKILL.md` | APIs, validation, business logic, jobs, integrations |
| `.claude/skills/frontend-architect/SKILL.md` | components, state, routing, rendering, client performance |
| `.claude/skills/database-architect/SKILL.md` | schemas, indexes, migrations, data integrity, growth |
| `.claude/skills/security-engineer/SKILL.md` | application security review |
| `.claude/skills/performance-engineer/SKILL.md` | diagnosing and improving performance |
| `.claude/skills/qa-engineer/SKILL.md` | test design, regression coverage, failure paths |
| `.claude/skills/ux-reviewer/SKILL.md` | navigation, forms, feedback states, ease of use |
| `.claude/skills/accessibility-reviewer/SKILL.md` | keyboard access, semantics, contrast, assistive technology |
| `.claude/skills/ai-engineer/SKILL.md` | prompts, context, evaluation, provider boundaries |
| `.claude/skills/devops-engineer/SKILL.md` | build, configuration, deployment, observability, backups |
| `.claude/skills/release-engineer/SKILL.md` | release readiness, rollback, release notes |
| `.claude/skills/refactoring-specialist/SKILL.md` | duplication, dead code, complexity, inconsistent patterns |
| `.claude/skills/code-standards-reviewer/SKILL.md` | consistency, naming, typing, project conventions |
| `.claude/skills/documentation-engineer/SKILL.md` | developer documentation, onboarding, runbooks |
| `.claude/skills/business-analyst/SKILL.md` | requirements, business rules, acceptance criteria |
| `.claude/skills/product-owner/SKILL.md` | user value, scope, prioritisation |

### Agents

Delegable roles. See "Important distinction" in `docs/USING-WITH-CLAUDE-CODE.md` for when to use an agent rather than a skill.

| Agent | Role |
| --- | --- |
| `.claude/agents/principal-architect.md` | technical ownership of cross-cutting change |
| `.claude/agents/code-reviewer.md` | correctness and risk review of a change |
| `.claude/agents/feature-planner.md` | feature request to implementation plan |

### Templates

| Template | Use for |
| --- | --- |
| `.claude/templates/ADR.md` | recording a significant architectural decision |
| `.claude/templates/project-claude.md` | creating a project's root `CLAUDE.md` |
| `.claude/templates/ysm-project-claude.md` | creating a YSM product's root `CLAUDE.md` using the YSM-Hub-compatible application/database baseline |

`scripts/validate-framework.py` fails if this catalogue omits a skill, standard, workflow, agent, or template that exists in the repository.
