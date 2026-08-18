---
name: security-review
description: Run the security review workflow: map trust boundaries and sensitive assets, review enforcement and untrusted input, and report findings with severity.
argument-hint: [area or change to review]
disable-model-invocation: true
---

# /security-review

Perform a security review of the scope described below, following this project's security review workflow.

Area or change to review: $ARGUMENTS

Read `.claude/workflows/security-review.md` now and follow its steps in order. That file is the
authoritative definition of this workflow and this one only starts it, so do not work from
a remembered version of the steps. Read the standards it names before making changes, and
consult a specialist skill from `.claude/skills/` only where it materially improves the work.

If nothing was described above, ask what to work on before starting.
