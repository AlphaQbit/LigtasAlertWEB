---
description: Inspect the diff, run lightweight checks, and request independent review for LigtasAlertWEB
agent: coordinator
---

Verify $ARGUMENTS.

1. **Inspect the final diff.** Review every change against the approved scope. Flag anything outside scope, any refactor that snuck in, and any file that should not have been touched.
2. **Run lightweight checks.** Delegate to the `tester` subagent: run the narrowest set of PHPUnit/Pest tests that cover the change, plus a Pint read-only check on changed PHP files. Report the exact commands and their output.
3. **Request independent review.** Launch the `reviewer` subagent for a read-only audit of the changed files. It must confirm or challenge the changes against security, architecture, API, migration, JS, and test quality.

Do not report success unless the tester's actual results and the reviewer's independent findings are in hand. Report any remaining risks and any unfinished work.