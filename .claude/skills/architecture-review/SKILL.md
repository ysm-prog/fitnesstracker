---
name: architecture-review
description: Run the architecture review workflow: map the current state, assess risks and complexity, and recommend a target state with a migration sequence.
argument-hint: [area to review]
disable-model-invocation: true
---

# /architecture-review

Review the architecture of the area described below, following this project's architecture review workflow.

Area to review: $ARGUMENTS

Read `.claude/workflows/architecture-review.md` now and follow its steps in order. That file is the
authoritative definition of this workflow and this one only starts it, so do not work from
a remembered version of the steps. Read the standards it names before making changes, and
consult a specialist skill from `.claude/skills/` only where it materially improves the work.

If nothing was described above, ask what to work on before starting.
