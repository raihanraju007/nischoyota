# ADR-007: Phase 1 database design

- **Status:** Accepted
- **Date:** 2026-10-06

## Context
Phase 1 needs one agreed schema before the first migration is written. The design in
[database.md](../database.md) was built from the prototype for the Bangladesh market.

## Options
1. **Adopt the full design and migrate it per feature**: one agreed target, small reviewable migrations.
2. **Design tables ad hoc per feature**: faster start, but cross-feature decisions (money, variants, snapshots) get made inconsistently.

## Decision
Option 1. Decisions D1–D9 in [database.md §2](../database.md#2-key-decisions-need-approval) are accepted:

| # | Decision |
|---|---|
| D1 | Phone is the main login ID; email optional; login with phone or email + password |
| D2 | SMS OTP only for phone verification at registration, password reset by phone, and guest COD orders from an unverified phone |
| D3 | Guest checkout; `orders.user_id` nullable; guests track by order number + phone |
| D4 | Money is an unsigned integer in paisa; currency is BDT only |
| D5 | Every product has at least one variant; price, SKU and stock live on the variant |
| D6 | Orders store snapshots of customer, address and product data |
| D7 | Seeded division/district/upazila tables with Bangla names; delivery charge comes from a delivery zone |
| D8 | Roles are a `role` column on `users` (see [ADR-012](ADR-012-admin-roles.md)); no permission package |
| D9 | Statuses and types are string columns cast to PHP enums |

## Consequences
- Migrations are created per feature, in roadmap order, not all at once.
- Any change to D1–D9 needs a new ADR that supersedes this one.
