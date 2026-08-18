# Enterprise Claude Code Framework

A reusable engineering framework for Claude Code projects.

## Purpose

Provide consistent architecture, coding, security, testing, UX, AI, documentation, and delivery practices across multiple repositories while allowing each project to retain its own business context.

## Structure

- `.claude/CLAUDE.md` — global engineering philosophy and operating rules
- `.claude/skills/` — reusable specialist skills, and one command per workflow
- `.claude/agents/` — reusable specialist agents
- `.claude/standards/` — engineering standards
- `.claude/workflows/` — repeatable development workflows
- `.claude/templates/` — reusable artifact templates, including the project context template
- `.claude/VERSION` — the framework version carried by a copied `.claude` directory
- `docs/` — framework documentation

## Use in a project

Install the `.claude` directory into the target repository and create a project-specific `CLAUDE.md` at the repository root using `.claude/templates/project-claude.md` as the starting point.

```bash
scripts/install-into.sh /path/to/project           # preview, writes nothing
scripts/install-into.sh /path/to/project --apply   # install the new files only
```

The installer never overwrites. A repository already using Claude Code has its own `.claude` directory, and copying over it replaces the project's own agents and skills with the framework's generic ones — so collisions are reported and left for you to reconcile. Take the parts the project lacks rather than all of them; see `docs/USING-WITH-CLAUDE-CODE.md`.

Keep business rules, domain context, current priorities, and project-specific constraints in the project repository. Keep general engineering rules in this framework.

See `docs/USING-WITH-CLAUDE-CODE.md` for the full bootstrap and update process.

## Versioning

Releases are recorded in `CHANGELOG.md`. The version a project copied is recorded in `.claude/VERSION`; compare the two before synchronising an updated `.claude` directory into a project.

## Validating a change

The framework is documentation, so its checks are structural rather than a build:

```bash
python3 scripts/validate-framework.py
```

This verifies that every skill and agent has loadable frontmatter naming it correctly, that repository paths mentioned in the documentation resolve, that `.claude/VERSION` matches the newest release in `CHANGELOG.md`, and that the catalogue in `docs/README.md` lists everything the framework ships. The same check runs in CI on every push and pull request.

When adding a skill, agent, standard, workflow, or template, list it in `docs/README.md`, add a `CHANGELOG.md` entry, and raise `.claude/VERSION` in the same change.

## Licence

MIT. See `LICENSE`.
