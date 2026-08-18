# Enterprise Claude Code Engineering Framework

## Mission

Build software that is simple, predictable, secure, testable, performant, maintainable, and easy to extend for 10+ years.

## Core principles

1. Understand before changing.
2. Preserve existing business behaviour unless explicitly approved.
3. Prefer the simplest solution that meets current and foreseeable needs.
4. Avoid speculative abstractions and unnecessary dependencies.
5. Keep responsibilities clear and boundaries explicit.
6. Make data ownership and business rules obvious.
7. Prefer readable code over clever code.
8. Validate changes with the project's available checks.
9. Update documentation when architecture or behaviour changes.
10. Treat security, accessibility, performance, and operability as engineering requirements.

## Change protocol

For non-trivial work:
- inspect relevant repository structure and project instructions;
- understand dependencies and affected workflows;
- state the plan before broad changes;
- make small, logically grouped changes;
- run the most relevant tests, type checks, linting, and build checks available;
- report assumptions, risks, and anything not verified.

Do not perform a large rewrite merely because a cleaner architecture is possible. Prefer incremental refactoring unless the existing design makes incremental work unsafe or disproportionately expensive.

## Specialist collaboration

Use the specialist skills in `.claude/skills/` when their expertise materially improves the task. The principal-architect skill coordinates cross-cutting work. Do not invoke every specialist for every task.

## Engineering standards

The standards in `.claude/standards/` are binding for work in their area. Read the relevant standard before reviewing or changing code it covers; do not read all of them for every task.

| Standard | Read it when the work involves |
| --- | --- |
| `.claude/standards/architecture.md` | module boundaries, dependency direction, service or integration design |
| `.claude/standards/coding.md` | writing or reviewing application code |
| `.claude/standards/review.md` | reviewing code, architecture, or a change of any kind |
| `.claude/standards/security.md` | authentication, authorization, untrusted input, secrets, or sensitive data |
| `.claude/standards/testing.md` | adding, changing, or reviewing tests and regression coverage |
| `.claude/standards/database.md` | schemas, queries, indexes, migrations, or data retention |
| `.claude/standards/performance.md` | latency, throughput, payload size, caching, or behaviour at scale |
| `.claude/standards/operations.md` | builds, configuration, deployment, monitoring, backups, or on-call response |
| `.claude/standards/ux.md` | user-facing interfaces, workflows, or accessibility |
| `.claude/standards/ai.md` | prompts, model calls, retrieval, or other LLM-backed behaviour |

Where a standard and a project-specific instruction conflict, the project instruction wins; say so explicitly rather than applying the standard silently.

## Workflows and templates

For recognisable kinds of work, follow the matching workflow:

- `.claude/workflows/new-feature.md`
- `.claude/workflows/bug-fix.md`
- `.claude/workflows/architecture-review.md`
- `.claude/workflows/refactoring.md`
- `.claude/workflows/dependency-upgrade.md`
- `.claude/workflows/security-review.md`
- `.claude/workflows/release.md`
- `.claude/workflows/incident-response.md`

Each workflow also has a command that starts it directly, named after the workflow: `/new-feature`, `/bug-fix`, and so on. Reaching for a workflow yourself, as described above, does not require the user to invoke its command.

Use `.claude/templates/ADR.md` when recording a significant architectural decision, and `.claude/templates/project-claude.md` when creating a project's root `CLAUDE.md`.

## Project context

The repository's root `CLAUDE.md` is project-specific and takes precedence for domain context, business rules, product requirements, and project constraints. This framework provides general engineering guidance; it must not invent project-specific requirements.

## Quality gates

Before declaring substantial work complete, verify where applicable:
- build succeeds;
- type checking succeeds;
- linting succeeds;
- relevant tests pass;
- migrations are safe and reversible where practical;
- no secrets or sensitive data were introduced;
- documentation is updated when needed;
- user-visible behaviour is preserved unless intentionally changed.

## Long-term design

Design for growth without prematurely designing for extreme scale. Consider data growth, observability, migrations, backwards compatibility, dependency longevity, operational simplicity, and developer onboarding.
