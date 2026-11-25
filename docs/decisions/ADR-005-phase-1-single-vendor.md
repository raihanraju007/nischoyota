# ADR-005: Phase 1 is single-vendor

- **Status:** Accepted
- **Date:** 2026-09-26

## Context
Multi-vendor and dropshipping are planned, but building them now would delay launch and add unused complexity.

## Decision
Phase 1 builds a single-vendor store. No vendor, commission, payout or supplier tables or code.

## Consequences
- A faster, simpler Phase 1.
- Phase 2 will need migrations and refactoring; [ADR-006](ADR-006-vendor-agnostic-core.md) keeps that cost low.
