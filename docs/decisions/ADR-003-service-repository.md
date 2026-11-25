# ADR-003: Controller → Service → Repository → Model

- **Status:** Accepted
- **Date:** 2026-09-26

## Context
We want thin controllers, reusable business logic and one place for queries, so that AI-generated code stays consistent.

## Decision
Controllers validate via FormRequests and call Services. Services hold business rules and transactions. Repositories hold all Eloquent queries. Concrete classes only; interfaces only when two real implementations exist. Full rules: [rules.md](../rules.md#1-layers).

## Consequences
- More files per feature, but a predictable place for everything.
- Services are unit-testable; repositories are tested through feature tests.
