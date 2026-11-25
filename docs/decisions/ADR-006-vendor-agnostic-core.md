# ADR-006: Vendor and supplier concepts stay out of unrelated logic

- **Status:** Accepted
- **Date:** 2026-09-26

## Context
Moving to multi-vendor later must not require rewriting the order, cart or inventory systems.

## Decision
Future-proof the architecture, not the codebase. No speculative vendor code, but:
- Seller-related logic (who sells, who fulfils, who is paid) is kept in clearly named service methods, not spread across controllers and views.
- Order totals, shipping and stock logic are computed in services so they can later be computed per seller.
- Concrete constraints are added to [architecture.md](../architecture.md#multi-vendor-readiness-phase-1-constraints) as they are agreed.

## Consequences
- Reviewers check that new code does not hard-code the assumption "there is only one seller" into unrelated modules.
- Anything touching this boundary is an architectural change and needs an ADR.
