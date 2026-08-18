# Using this framework with Claude Code

## Recommended model

Keep this repository as the single source of truth for reusable engineering guidance. Each application repository should contain its own project-specific `CLAUDE.md` and, when needed, its own project-specific `.claude/` additions.

## Bootstrap a project

From the framework repository, preview what installing into the project would do:

```bash
scripts/install-into.sh /path/to/project
```

The preview writes nothing. It lists the files that would be added, the files that already exist in the project, and how many skills the project would end up with. When the result looks right:

```bash
scripts/install-into.sh /path/to/project --apply
```

Then create a root `CLAUDE.md` containing the project's purpose, users, technology, business rules, current priorities, constraints, integrations, and development commands. Use `.claude/templates/project-claude.md` as the starting point.

Do not copy the directory with `cp -R`. Any repository already using Claude Code has its own `.claude` directory, and a file present on both sides is silently replaced by the framework's version. That is a real loss, not a theoretical one: a project's own `code-reviewer` agent knows its tenancy rules, its money arithmetic, and which CI guard enforces what, and the framework's generic replacement knows none of it. The installer never overwrites; it reports collisions and leaves them for you.

## Adopting into a repository that already has `.claude/`

Most repositories worth adopting this into are not empty, so treat installation as a merge rather than a copy.

**Take what the project lacks, not everything.** Copying nine standards to gain the two a project is actually missing does not make it nine times better guided — it makes the two harder to find. Compare the framework's standards against what the project's own `CLAUDE.md` and skills already cover, and install the gaps. In practice a mature project has usually thought harder about its own security, database, and domain rules than any general framework can, while leaving accessibility, UX, and operability thin. Those thin areas are where this framework earns its place.

**Reconcile collisions in the project's favour by default.** When the same name exists on both sides, the project's version is normally better, because it is specific. Keep it. Take from the framework only the parts the project's version is missing, and fold them in by hand.

**Watch for overlapping names that do not collide as files.** A project skill called `security-audit` and this framework's `/security-review` command do not overwrite each other, but they do compete for the same job, and having both invites picking the weaker one. Where the project already has a specific entry point for a kind of work, prefer it and leave the framework's generic one out.

**The framework deliberately ships no `settings.json`.** Permissions and hooks are project decisions, and a framework file of that name would overwrite working configuration on installation.

## Keep project and global context separate

Global framework:
- engineering principles
- reusable skills
- reusable agents
- standards
- workflows
- templates

Project repository:
- business rules
- product requirements
- domain terminology
- current architecture
- project-specific commands
- project-specific constraints

## Start Claude Code

Run Claude Code from the root of the application repository. Claude Code will read the repository's instructions and can use the skills and agents available under `.claude/`.

Each workflow has a command named after it, so the common kinds of work can be started directly:

```text
/bug-fix orders over £500 are rejected at checkout
```

```text
/security-review the new file upload endpoint
```

The commands are thin: each reads its workflow from `.claude/workflows/` and follows it, so the workflow file stays the single definition. They do not load themselves automatically, which keeps them free of context cost until you invoke one.

Claude also reaches for the right workflow on its own, as directed by `.claude/CLAUDE.md`, so the commands are a convenience rather than the only route in.

Typical requests:

```text
Review this repository's architecture before making changes.
```

```text
Plan this feature, then show me the implementation sequence before coding.
```

```text
Use the database architect skill to review this schema.
```

```text
Review this change for security, performance, UX, and regression risk.
```

## Updating the framework

When the framework improves, pull the latest version and intentionally update the `.claude` directory in projects. Do not blindly overwrite project-specific Claude instructions.

A practical approach is to keep the framework as a versioned private Git repository and copy or synchronise its `.claude` directory into application repositories. This makes each application self-contained and reproducible.

Each copied `.claude` directory carries a `.claude/VERSION` file recording the framework version it came from. To update a project:

1. Read the project's `.claude/VERSION`.
2. Read `CHANGELOG.md` in the framework for everything released since that version.
3. Apply the changes deliberately, keeping any project-specific additions under `.claude/`.
4. The new `.claude/VERSION` arrives with the copied directory and records the update.

## Changing the framework itself

Run the structural check before committing a framework change:

```bash
python3 scripts/validate-framework.py
```

It verifies skill and agent frontmatter, that documented repository paths resolve, that `.claude/VERSION` matches the newest `CHANGELOG.md` release, and that the catalogue in `docs/README.md` lists everything the framework ships. CI runs the same check.

Adding or removing a skill, agent, standard, workflow, or template is a framework release: list it in `docs/README.md`, record it in `CHANGELOG.md`, and raise `.claude/VERSION` in the same change.

## Important distinction

A **skill** is a reusable area of expertise stored as `.claude/skills/<name>/SKILL.md`.

An **agent** is a reusable specialist persona stored as `.claude/agents/<name>.md`.

The framework uses both. Skills are the preferred mechanism for reusable instructions and specialist expertise; agents are useful for focused, independently delegated roles.

The practical difference is context. A skill's guidance is applied inside the current session, which already holds the conversation and everything read so far. An agent runs separately: it starts without that context and returns a written result. So an agent needs to gather its own context and produce a deliverable that stands on its own, while a skill can rely on the surrounding work.

### When a role exists as both

`principal-architect` appears as a skill and as an agent. They are not duplicates and the content is not repeated: the skill holds the expertise — the process to follow and the output a review must contain — and the agent holds only what changes when the role is delegated, pointing at the skill for the rest.

Use the skill when architectural judgement is needed in the work already in progress. Use the agent when the review is a self-contained piece of work whose result will be read on its own.

Apply the same rule when adding a role. Put the expertise in a skill. Add an agent only when the role is genuinely worth delegating, and have it reference the skill rather than restate it — two copies of the same guidance will drift.

### Agents in this framework

| Agent | Role | Reads |
| --- | --- | --- |
| `.claude/agents/principal-architect.md` | Technical ownership of cross-cutting change | `.claude/skills/principal-architect/SKILL.md` |
| `.claude/agents/code-reviewer.md` | Correctness and risk review of a change | `.claude/skills/code-standards-reviewer/SKILL.md`, `.claude/skills/security-engineer/SKILL.md`, `.claude/skills/qa-engineer/SKILL.md` |
| `.claude/agents/feature-planner.md` | Feature request to implementation plan | `.claude/workflows/new-feature.md`, `.claude/skills/business-analyst/SKILL.md`, `.claude/skills/solution-architect/SKILL.md` |
