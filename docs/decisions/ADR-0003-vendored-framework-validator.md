# ADR-0003: The vendored framework validator excludes dependency directories

- **Status:** Accepted
- **Date:** 2026-08-18

## Context

`scripts/validate-framework.py` arrived with the Enterprise Claude Code
Framework, which is a documentation repository. One of its checks walks every
`*.md` file and fails if an inline-code token beginning `.claude/`, `docs/`, or
`scripts/` does not resolve. In the framework's own repository that is exactly
right.

In an application repository it is not. Installing Composer dependencies added
`vendor/nette/utils/AGENTS.md` and others, which reference an internals document
by a `docs/`-prefixed path of their own. CI went red on documentation belonging
to a third-party package.

## Decision

The validator skips `.git/`, `vendor/`, `node_modules/`, `storage/`, and
`frontend/.next/` when scanning documented paths. The divergence from upstream is
noted in the script's own docstring.

## Consequences

The check keeps doing its real job: this repository's documentation cannot
reference a path that does not exist — which it caught immediately when
`CLAUDE.md` was written before the documents it links to.

The cost is that this copy of the script now differs from the framework's. Before
synchronising a newer `.claude/` into this repository, compare
`.claude/VERSION` (currently 1.5.0) against the framework's `CHANGELOG.md` and
re-apply this exclusion if the script is overwritten.
