# F-025: Admin order management — Tasks

Status: `[ ]` todo · `[~]` doing · `[x]` done. Tick items as they finish. Commit after each green group.

## Database
- [x] Migration: courier, tracking, admin note, delivered/cancelled timestamps
- [x] Fix: `orders.placed_at` / `otp_codes.expires_at` lost their MariaDB `ON UPDATE CURRENT_TIMESTAMP` (every update rewrote them)

## Backend
- [x] `OrderStatus` transition rules, `OrderDateRange`, `AdminOrderFilter`, `OrderStatusChangeData`
- [x] `OrderRepository` admin queries (list, counts, pending count, customer order count, payment updates)
- [x] `OrderStatusService` (transition, cancel side effects shared with customer cancel, return, mark paid), `AdminOrderService`
- [x] `InventoryService::restockForReturnedOrder()`
- [x] `Admin\OrderController`, requests, routes; static routes removed
- [x] Pending badge composer on the admin layout

## Frontend
- [x] `admin/orders/index` (tabs, search, date range, table, pagination, empty state)
- [x] `admin/orders/show` (items, totals, history, status form, cancel modal, customer, address, payment, note)
- [x] Courier + tracking on the customer order page

## Tests
- [x] List, tabs/counts, search, date range, staff access, customer 403
- [x] Transitions allowed/refused, history row, shipped needs courier, delivered_at
- [x] Cancel (stock, coupon, payment, event), return (stock, refund), mark paid
- [x] Badge

## Verification
- [x] Narrow tests pass
- [x] `vendor/bin/pint --dirty --format agent`

## Documentation
- [x] Status, roadmap, memory, index
