# ADR-013: Inventory owns the stock column

- **Status:** Accepted
- **Date:** 2026-10-06

## Context
Stock is stored on Catalog's `product_variants.stock_quantity` (D5), but the `Inventory` module (ADR-008) must change it
for adjustments now and for orders later, and every change must be logged in `stock_movements`.
Cross-module rule: a module calls another module's service, never its models or repositories.

## Options
1. **Inventory owns the column**: only `InventoryService` writes `stock_quantity` (row lock + movement in one transaction). Catalog stops writing stock.
2. **Catalog exposes stock methods** that Inventory calls: stricter separation, but stock logic lives in two modules.

## Decision
Option 1. Documented exception to the cross-module rule:
- `Inventory` may **read** `products` / `product_variants` (names, SKUs, stock) for its pages and queries.
- `Inventory` is the **only writer** of `product_variants.stock_quantity`. Catalog (product form, variants table) shows stock read-only and links to Inventory; it creates variants with stock 0 and asks `InventoryService` for opening stock.
- Orders (F-023) change stock only through `InventoryService`.

## Consequences
- Every stock change has a movement row (who, why, how much, balance after).
- Reviews check that no code outside `InventoryService` updates `stock_quantity`.
