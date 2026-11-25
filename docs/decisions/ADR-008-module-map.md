# ADR-008: Module map for Phase 1

- **Status:** Accepted
- **Date:** 2026-10-06

## Context
[ADR-001](ADR-001-modular-monolith.md) splits the app into modules, but did not say which ones.
Without a fixed map, each feature would invent its own boundary.

## Decision

| Module | Owns |
|---|---|
| `Account` | users, roles, authentication, OTP, addresses |
| `Location` | divisions, districts, upazilas, delivery zones |
| `Catalog` | categories, brands, attributes, products, variants, images |
| `Inventory` | stock movements; stock adjust / decrease / increase |
| `Shopping` | cart, wishlist |
| `Promotion` | coupons and coupon validation |
| `Order` | checkout, orders, items, status history, payments |
| `Review` | reviews and the product rating cache |
| `Content` | CMS pages, contact messages, store settings |
| `Dashboard` | read-only admin reports, built on other modules' services |

Cross-cutting code that belongs to no business module (module loading, money formatting, admin
middleware) lives in `app/Support/` and `app/Http/`.

## Consequences
- A new module needs a new ADR.
- `app/Models/User.php` moves to `App\Modules\Account\Models\User` in F-002.
