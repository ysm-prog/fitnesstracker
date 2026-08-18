---
name: new-feature
description: Run the new feature workflow: clarify the outcome, inspect existing patterns, design the smallest coherent change, implement, test, and verify.
argument-hint: [feature to build]
disable-model-invocation: true
---

# /new-feature

Build the feature described below, following this project's new feature workflow.

Feature to build: $ARGUMENTS

Read `.claude/workflows/new-feature.md` now and follow its steps in order. That file is the
authoritative definition of this workflow and this one only starts it, so do not work from
a remembered version of the steps. Read the standards it names before making changes, and
consult a specialist skill from `.claude/skills/` only where it materially improves the work.

If nothing was described above, ask what to work on before starting.
