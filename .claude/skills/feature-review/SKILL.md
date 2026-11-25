---
name: feature-review
description: Read-only review of an implemented feature (F-NNN) against its spec, the architecture and the coding rules. Reports issues by severity. Use when the user asks to review a feature.
argument-hint: <F-NNN>
disable-model-invocation: true
---

# Review $ARGUMENTS (read-only)

**Do not modify code.** Report only.

1. Read the feature's `feature.md` and `tasks.md`, `docs/rules.md`, `docs/architecture.md` and `docs/decisions.md`.
2. Find the changed files (`git diff master...HEAD`, `git status`, or the files listed in `tasks.md`) and read them in full.
3. Check each area:
   - **Requirements:** every acceptance criterion is implemented and has a test
   - **Architecture:** layering respected, no cross-module repository access, no silent architectural decisions
   - **Security:** FormRequest validation, policies, server-side prices and stock, mass assignment, output escaping
   - **Database:** migrations, indexes, FKs, N+1 queries, transactions around multi-write operations
   - **Edge cases** listed in `feature.md` §10
   - **Tests:** meaningful assertions, including authorization and validation paths
   - **Code quality:** `docs/rules.md` §3–4 (naming, duplication, dead code, needless abstraction)
   - **Multi-vendor:** ADR-006 not violated
4. Run the feature's tests and report the result.
5. Output the issues grouped **Critical / High / Medium / Low**, each with `file:line`, the problem and a suggested fix. End with a verdict: *Ready*, *Ready after fixes*, or *Needs rework*.
