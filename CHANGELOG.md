# Changelog

All notable changes to this framework are recorded here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and versions follow
[Semantic Versioning](https://semver.org/spec/v2.0.0.html) as applied to guidance
rather than code:

- **Major** — guidance changes that projects must review before adopting, such as a
  removed or renamed skill, workflow, or standard.
- **Minor** — new skills, standards, workflows, or templates.
- **Patch** — clarifications and corrections that do not change what the framework asks for.

The version a project has copied is recorded in `.claude/VERSION`. Compare it against
this file before synchronising an updated `.claude` directory into a project.

## Unreleased

### Added

- `.claude/templates/ysm-project-claude.md` — root `CLAUDE.md` template for YSM products using the standard application architecture and YSM-Hub-compatible Supabase/RLS baseline.
- `docs/YSM-PROJECT-SCAFFOLD.md` — reusable YSM project architecture and database contract.
- Documentation catalogue entries for the YSM scaffold.

### Changed

- New-project guidance now treats YSM-Hub (`ysm-prog/ysm-hub`) as the reference implementation for tenant isolation, RLS, canonical schema, migrations, and database security testing without requiring new products to copy its business schema or UI.

## [1.5.0] - 2026-08-09

Found by using the framework to run a real accessibility review on a real repository.
The review produced genuine findings, and would have produced three false ones if its
skill had been followed literally.

### Added

- `.claude/standards/review.md`, covering how to conduct any review rather than what to
  look for in one area. Three sections, each written for an observed failure:
  - **Establish the project's position first.** The project had already logged an
    accessibility follow-up. Nothing in the framework told the reviewer to look.
  - **Confirm before reporting.** A search result is a candidate, not a finding. A
    repository with 440 `<label>` elements looked correctly labelled and was not; a
    match inside a code comment looked like a defect and was not; a file that scored worst on
    a repository-wide metric turned out to be the one file doing it correctly.
  - **Rank by who is affected.** The skills said to separate blockers from friction but
    gave no basis for judging. The same defect on an internal tool used by six staff and
    on a public page are not the same finding.

### Changed

- Every skill and agent that reviews now applies the review standard, as do the
  architecture review and security review workflows. documentation-engineer and
  feature-planner do not — they produce work rather than review it.
- business-analyst and product-owner now reference a standard for the first time. Nothing
  covered their area before; the review standard does.

## [1.4.0] - 2026-08-09

Found by installing the framework into a real repository for the first time.

### Fixed

- The documented bootstrap was destructive. It said to run
  `cp -R /path/to/enterprise-claude-framework/.claude ./`, which silently overwrites any
  file the project already has under `.claude/`. Against the first real repository it was
  tried on, it replaced a project-specific `code-reviewer` agent — one that knew the
  project's tenancy rules, money arithmetic, and CI guards — with the framework's eight
  generic lines, with no warning. The documentation warned against blind overwriting only
  when *updating* a project, not when bootstrapping one.

### Added

- `scripts/install-into.sh`, which installs `.claude/` without overwriting anything.
  It previews by default, requires `--apply` to write, reports every collision instead of
  resolving it, and shows the resulting skill count.
- Guidance for adopting into a repository that already has a `.claude/` directory, which
  is the normal case: install the gaps rather than everything, reconcile collisions in the
  project's favour, and watch for overlapping entry points that compete without colliding
  as files.

### Notes

- The framework deliberately ships no `settings.json`. Permissions and hooks are project
  decisions, and a framework file of that name would overwrite working configuration on
  installation. This was going to be proposed as a future addition; the trial showed it
  would have made the framework more destructive rather than less.

## [1.3.0] - 2026-08-09

### Added

- `.claude/standards/performance.md` and `.claude/standards/operations.md`.

### Changed

- performance-engineer and devops-engineer now apply their own standards. They were the
  only skills borrowing standards written for another area: performance-engineer pointed at
  the database and architecture standards, and devops-engineer at the security standard,
  because no performance or operations standard existed.
- release-engineer, the release workflow, and the incident response workflow apply the
  operations standard. frontend-architect and the architecture review workflow apply the
  performance standard.
- business-analyst, documentation-engineer, and product-owner still reference no standard.
  No standard covers their area, and a forced reference would be worse than none.

## [1.2.0] - 2026-08-09

### Added

- A command for each workflow — `/new-feature`, `/bug-fix`, `/architecture-review`,
  `/refactoring`, `/dependency-upgrade`, `/security-review`, `/release`,
  `/incident-response`. The workflows were guidance that only applied if the user already
  knew it existed; each command is now an entry point that reads its workflow and follows
  it. The workflow file remains the definition and the command does not restate it.
- A validation check that every workflow has a command invoking it, and that the command
  references its own workflow rather than a neighbour's.

### Notes

- The commands are skills rather than files in a commands directory. Custom commands have
  been merged into skills, and a project skill takes precedence over both a command and a
  bundled skill of the same name — which matters here, since a bundled `security-review`
  skill would otherwise shadow the framework's own.
- They set `disable-model-invocation: true`, so they cost no context until invoked.
  `.claude/CLAUDE.md` continues to direct Claude to the workflow files directly, so
  automatic use of a workflow does not depend on the user typing a command.

## [1.1.0] - 2026-08-09

### Added

- Three workflows: `.claude/workflows/dependency-upgrade.md`,
  `.claude/workflows/security-review.md`, and `.claude/workflows/incident-response.md`.
- `docs/README.md`, a documentation index and a catalogue of every standard, workflow,
  skill, agent, and template the framework ships.
- Concrete checks in the accessibility-reviewer, ai-engineer, and devops-engineer skills,
  which previously restated their own description without saying what to look for.
- A validation check that fails when the `docs/README.md` catalogue omits something the
  repository ships.

### Changed

- The three agents now state what changes when a role is delegated: they gather their own
  context and return a self-contained deliverable. `.claude/agents/principal-architect.md`
  references the principal-architect skill for the expertise rather than restating it, so
  the two cannot drift apart.
- `docs/USING-WITH-CLAUDE-CODE.md` explains when a role should exist as a skill, as an
  agent, or as both.
- `.claude/CLAUDE.md` lists the workflows by full path, so the validator checks them.

## [1.0.0] - 2026-08-09

### Added

- Global engineering instructions in `.claude/CLAUDE.md`: mission, core principles,
  change protocol, quality gates, and long-term design guidance.
- Eighteen specialist skills in `.claude/skills/`, covering architecture, backend,
  frontend, database, security, performance, QA, UX, accessibility, AI engineering,
  DevOps, release, refactoring, documentation, code standards, business analysis,
  and product ownership.
- Three specialist agents in `.claude/agents/`: principal-architect, code-reviewer,
  and feature-planner.
- Seven engineering standards in `.claude/standards/`: architecture, coding, security,
  testing, database, UX, and AI.
- Five workflows in `.claude/workflows/`: new feature, bug fix, architecture review,
  refactoring, and release.
- Templates in `.claude/templates/`: an architecture decision record and a project
  `CLAUDE.md` starting point.
- Framework documentation in `README.md` and `docs/USING-WITH-CLAUDE-CODE.md`.
- `.claude/VERSION`, so a copied `.claude` directory records the framework version
  it came from.
- Continuous integration that validates skill and agent frontmatter, checks that
  documented repository paths resolve, and keeps `.claude/VERSION` in step with
  this changelog.

### Fixed

- `README.md` referenced a "bootstrap" directory and project template file that do
  not exist, which broke the documented project bootstrap step. It now points at
  `.claude/templates/project-claude.md`.
- The standards in `.claude/standards/` were referenced only by the documentation,
  so no Claude Code session loaded them. They are now referenced from
  `.claude/CLAUDE.md` and from each skill, workflow, and agent they cover.
