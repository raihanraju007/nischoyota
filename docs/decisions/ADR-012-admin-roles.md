# ADR-012: Admin and staff roles

- **Status:** Accepted
- **Date:** 2026-10-06

## Context
D8 gives `users.role` the values `customer`, `staff` and `admin`. What staff may do was not defined.

## Decision
- `customer`: storefront only, no access to `/admin`.
- `staff`: admin panel for orders, products, categories, brands, attributes, stock, reviews and customers.
- `admin`: everything staff can do, plus store settings, delivery zones, coupons, CMS pages, staff accounts, and seeing product cost prices.

Checks use Laravel Gates/Policies built on `UserRole` methods (e.g. `$role->canManageStore()`). No permission package.

## Consequences
- Every admin-only route and field is covered by a test that staff gets 403.
- Finer permissions later would need a new ADR.
