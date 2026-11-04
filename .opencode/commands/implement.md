---
description: Clarify scope, plan, implement narrowly, and report changed files for LigtasAlertWEB
agent: coordinator
---

Implement $ARGUMENTS.

First, inspect the working tree (`git status`) and the relevant code. If the task is ambiguous or risky, present a scoped plan and wait for the user's approval before proceeding. If the scope is clear, proceed directly.

Delegate the approved implementation to the `implementer` subagent with an explicit scope: name the exact files, routes, or areas, and the expected output.

Rules:
- Never overwrite existing user changes. If a target file already has uncommitted modifications, stop and report them.
- Do not commit, push, reset, clean, or delete files.
- Do not change dependencies without approval.
- After implementation, list every file you created or modified, and note anything deliberately left untouched and why.