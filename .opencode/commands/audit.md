---
description: Perform read-only parallel reviews of LigtasAlertWEB and consolidate findings
agent: coordinator
subagent: true
---

Audit $ARGUMENTS.

Delegate independent read-only reviews to the `reviewer` subagent. If the argument names specific areas (e.g. routes, migrations, auth), launch parallel reviewer subagents — one per disjoint area — so they can run simultaneously. If no areas are given, start with a broad review covering routes, middleware, authentication, API behavior, database migrations, JavaScript, and PHPUnit/Pest tests.

Each reviewer must return: severity, affected file/line, evidence, and recommended fix.

Consolidate the findings into a single evidence-based report. Do not modify any application files. Do not commit or push anything.