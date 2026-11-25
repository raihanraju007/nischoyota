# F-008: Inventory

- **Status:** Done
- **Module:** `app/Modules/Inventory` (new, ADR-008)
- **Phase:** 1
- **Depends on:** F-006, F-007, ADR-013

## 1. Objective
Every stock change is made through Inventory and logged (who, why, how much, balance after). Staff see
stock levels, low and out-of-stock variants, and adjust stock with a reason.

## 2. User stories
- As **staff/admin**, I want to see all variants with their stock and find low or empty ones.
- As **staff/admin**, I want to add stock (restock), remove damaged items, or set the counted quantity, with a note.
- As the **owner**, I want a history of every change for each variant.

## 3. Business rules
1. `StockMovementReason`: opening_stock, restock (+), damaged (−), stock_count (set exact), order_placed (−), order_cancelled (+), returned (+). Manual reasons in the admin: restock, damaged, stock_count.
2. Quantity for restock/damaged: 1–100,000. For stock_count: the counted number 0–1,000,000 (change = counted − current; no row when equal). Note optional (max 255).
3. Stock can never go below 0 ("Only N in stock").
4. Each change locks the variant row, updates `stock_quantity` and writes a movement with `stock_after` in one transaction (ADR-013).
5. New products: the form's **Opening stock** is logged as `opening_stock`. When editing a product or its variants, stock is read-only with an "Adjust stock" link. New variant combinations start at 0.
6. Low stock = 1–5 (`ProductVariant::LOW_STOCK_THRESHOLD`; becomes a store setting in F-010). Out of stock = 0.
7. Staff and admins can adjust stock (ADR-012).

## 4. Scope
- `Inventory` module: `stock_movements` table, model, enum, repository, `InventoryService`, controller, routes
- 🎨 `/admin/inventory`: all variants (product, variant, SKU, stock), tabs All / Low stock / Out of stock with counts, search by name/SKU, 25 per page
- 🎨 `/admin/inventory/{variant}`: variant summary, Adjust form, movement history (newest first, paginated)
- Product form and variants table: stock read-only + link; opening stock via `InventoryService`
- Sidebar link "Inventory"; seeder logs opening stock

## 5. Out of scope
- Order stock changes (F-023 uses `InventoryService`), dashboard low-stock widget (F-028), stock threshold setting (F-010)

## 7. Database requirements
| Table | Columns | Notes |
|---|---|---|
| stock_movements | `product_variant_id` FK cascade, `quantity_change` int, `stock_after` int, `reason`, `order_id` nullable (FK added in F-023), `user_id` FK users null on delete, `note` nullable, `created_at` | no `updated_at`; rows are never edited. `stock_after` added to the design so history shows the balance |

## 8. Routes / API
| Method | URI | Name |
|---|---|---|
| GET | /admin/inventory | admin.inventory.index |
| GET | /admin/inventory/{variant} | admin.inventory.show |
| POST | /admin/inventory/{variant}/adjustments | admin.inventory.adjust |

## 9. Security requirements
- Admin middleware; reason restricted to manual reasons; quantity bounds; row lock against concurrent changes

## 10. Edge cases
- Removing more than in stock → error, nothing changes
- Stock count equal to current → "No change" message, no row
- Product deleted → its movements are deleted with its variants

## 11. Acceptance criteria
- [x] Adjust: restock / damaged / stock count update stock and log a movement with user, note and balance
- [x] Never negative; equal count makes no row
- [x] Opening stock logged on product create (simple product)
- [x] Stock read-only in product form and variants table; posted stock ignored
- [x] Inventory list tabs, counts, search, pagination; history page
- [x] Guests/customers kept out

## 12. Multi-vendor impact
Phase 2 vendors manage their own variants' stock through the same `InventoryService`; queries would add a vendor filter.

## 13. Open questions
- [x] Inventory owns the stock column — ADR-013, approved 2026-10-06
- [x] Opening stock on create, then read-only — approved 2026-10-06
