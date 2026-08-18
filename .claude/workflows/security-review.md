# Security Review Workflow

1. Establish scope: the change under review, or the area of the system being audited. State what is in scope and what is not.
2. Map the trust boundaries the code crosses: unauthenticated users, authenticated users of different roles, other services, background jobs, and third-party input.
3. Identify the sensitive assets involved: credentials, personal data, financial records, and any data whose exposure would matter.
4. Review authentication and authorization at each boundary. Confirm enforcement happens server-side, on every path to the resource, not only on the path the interface exposes.
5. Review untrusted input handling: validation at the boundary, parameterised queries, output encoding, file handling, and deserialisation.
6. Review secrets and configuration: how they are stored, whether they reach logs or error responses, and what an attacker learns from a failure message.
7. Review the change for data exposure: over-broad responses, missing filtering by tenant or owner, and personal data in logs or analytics.
8. Consider abuse of legitimate functionality — enumeration, rate limits, expensive operations, and workflows that can be replayed or reordered — not only classic injection.
9. Confirm dependencies in scope have no known unpatched advisories.
10. Report each finding with its severity, the concrete conditions under which it is exploitable, and the smallest correct remediation.

Do not report a theoretical issue as a confirmed vulnerability. State what was verified and what was inferred. Never include working credentials, tokens, or captured personal data in a report.

Apply `.claude/standards/review.md`, `.claude/standards/security.md` and `.claude/standards/database.md` throughout.
