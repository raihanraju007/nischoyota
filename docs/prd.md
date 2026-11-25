# Product Requirements — Nischoyota

> Status: **Draft** — sections marked `TODO` are filled in step by step.

## 1. Vision

TODO: One paragraph. What is Nischoyota, for whom, and why would they use it over alternatives?

## 2. Target users

| User | Goal |
|---|---|
| Customer | TODO |
| Admin / Store staff | TODO |
| Vendor *(Phase 2)* | TODO |
| Supplier *(Phase 3)* | TODO |

## 3. Phases

| Phase | Scope | Status |
|---|---|---|
| **1 — Single vendor** | The store sells its own products. Catalog, cart, checkout, orders, inventory, delivery, admin. | In progress |
| 2 — Multi-vendor | Third-party vendors list products; commission and payouts. | Future |
| 3 — Dropshipping | Suppliers fulfil orders directly. | Future |

Phase 2 and 3 are documented so Phase 1 does not block them. **They are not built now.**
See [ADR-005](decisions/ADR-005-phase-1-single-vendor.md).

## 4. Phase 1 capabilities

TODO: list capabilities at business level (not implementation). Each becomes one or more features in `features/`.

- Customer accounts (register, login, profile, addresses)
- Product catalog (categories, brands, products, search, filters)
- Cart and wishlist
- Checkout and payment
- Orders and order tracking
- Inventory
- Delivery / shipping
- CMS pages (policies, about, contact)
- Admin panel

## 5. Business rules (global)

TODO: currency, tax/VAT, languages, payment methods, delivery areas, return window.

## 6. Non-functional requirements

TODO: performance targets, supported browsers/devices, SEO, security, availability.

## 7. Out of scope (Phase 1)

- Vendor accounts, commissions, payouts
- Supplier / dropship fulfilment
- TODO

## 8. Future capabilities (for architectural awareness only)

TODO: what Phase 2/3 will need, so Phase 1 avoids blocking it (e.g. product ownership, order splitting per seller).
