---
name: feature-explore
description: Read-only exploration of a feature before implementation. Inspects the spec and existing code and proposes an implementation plan. Use when the user asks to explore, analyze or plan a feature (F-NNN).
argument-hint: <F-NNN>
disable-model-invocation: true
---

# Explore $ARGUMENTS (read-only)

**Do not modify any file.** This mode only investigates and reports.

1. Read `docs/features/$ARGUMENTS*/feature.md` and `tasks.md`, then `docs/memory.md`, `docs/architecture.md`, `docs/rules.md` and `docs/decisions.md`.
2. Inspect the existing code relevant to the feature: modules in `app/Modules/`, models, migrations, services, repositories, controllers, requests, policies, routes, views and tests. Use the Boost `database-schema` tool for the DB.
3. Find the closest existing implementation to copy the pattern from. If none exists yet, say so; this feature will set the pattern.
4. Report:
   - **Spec gaps:** unclear or missing rules, and open questions still unanswered
   - **Existing code** to reuse or change (file paths)
   - **Dependencies** on other features and modules
   - **Risks:** security, data integrity, performance, multi-vendor impact (ADR-006)
   - **Architectural decisions needed.** If any, write them as a proposal (current state → change → impact → options → recommendation). Do not decide them.
   - **Proposed plan:** ordered steps matching the task groups in `tasks.md`, listing the files to create or modify
5. Ask the user to approve the plan or answer the questions. Implementation happens only with `/feature-implement`.
