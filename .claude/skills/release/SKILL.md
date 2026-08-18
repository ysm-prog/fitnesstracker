---
name: release
description: Run the release workflow: confirm scope, review migrations and compatibility, define smoke tests and rollback, and verify the deployed behaviour.
argument-hint: [version or scope]
disable-model-invocation: true
---

# /release

Prepare the release described below, following this project's release workflow.

Version or scope: $ARGUMENTS

Read `.claude/workflows/release.md` now and follow its steps in order. That file is the
authoritative definition of this workflow and this one only starts it, so do not work from
a remembered version of the steps. Read the standards it names before making changes, and
consult a specialist skill from `.claude/skills/` only where it materially improves the work.

If nothing was described above, ask what to work on before starting.
