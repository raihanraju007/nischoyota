# F-025: Admin order management

- **Status:** Done
- **Module:** `app/Modules/Order`
- **Phase:** 1
- **Depends on:** F-024

## 1. Objective
Staff confirm, pack, ship and close orders from the admin panel, with a full history of who changed what.

## 2. User stories
- As **staff**, I want to see new orders first, find any order by number, phone or name, and filter by status and date.
- As **staff**, I want to move an order through its statuses, add the courier and tracking code, and keep internal notes.
- As **staff**, I want to mark a COD order paid when the courier pays us, and cancel or take back a returned order with the stock restored.

## 3. Business rules
1. `/admin/orders` (staff + admin): tabs All, Pending, Confirmed, Processing, Shipped, Delivered, Cancelled, Returned with counts; search by order number (with or without `#`), phone or customer name; date range Today / Last 7 days / Last 30 days / This year / All time (Bangladesh days); newest first, 20 per page.
2. **Allowed transitions** (`OrderStatus::allowedNextStatuses()`):
   - Pending → Confirmed, Cancelled
   - Confirmed → Processing, Cancelled
   - Processing → Shipped, Cancelled
   - Shipped → Delivered, Returned
   - Delivered → Returned
   - Cancelled, Returned: final
   Anything else is refused and nothing changes. The check runs under a row lock.
3. Every change writes a status history row with the staff user and the optional note.
4. **Shipped** needs a courier name (typed in the status form or already saved). Courier name and tracking code are saved with any status change.
5. **Delivered** sets `delivered_at`. **Cancelled** sets `cancelled_at`, restores stock as taken (`InventoryService::restockForCancelledOrder`), releases the coupon use and dispatches `OrderCancelled` (same as a customer cancel, shared code).
6. **Returned** restores stock (`InventoryService::restockForReturnedOrder`, movement reason `returned`); the coupon use is kept.
7. **Payment on cancel/return:** a paid order gets a `refunded` payment row for the paid amount and `payment_status = refunded`; an unpaid order's pending payment becomes `cancelled`.
8. **Mark COD paid:** only while the payment is pending and the order is not cancelled/returned. Pending payment row → `paid` with `paid_at`; `orders.payment_status = paid`.
9. **Admin note:** internal text (max 2000), never shown to customers.
10. Sidebar "Orders" badge and the top-bar bell show the number of **pending** orders (hidden at 0).
11. Customers see the courier and tracking code on their order page once set.

## 4. Scope
- Migration: `orders.courier_name`, `tracking_code`, `admin_note`, `delivered_at`, `cancelled_at`
- `OrderStatus::allowedNextStatuses()/canTransitionTo()/isFinal()/adminFilters()`, `OrderDateRange`, `AdminOrderFilter`, `OrderStatusChangeData`
- `OrderStatusService` (change status, mark paid, cancellation shared with the customer cancel), `AdminOrderService` (list, counts, detail, note, history)
- `InventoryService::restockForReturnedOrder()`
- `Admin\OrderController` + requests; admin layout composer for the pending badge
- Views `admin/orders/index`, `admin/orders/show`

## 5. Out of scope
- Printing (F-025b), notifications/SMS (F-029), courier API integration, partial returns/refunds, export, editing order items

## 7. Database requirements
New migration adding to `orders`: `courier_name` string(50) null, `tracking_code` string(50) null, `admin_note` text null, `delivered_at` / `cancelled_at` timestamp null.

## 8. Routes
| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/admin/orders` | `admin.orders.index` | `index` |
| GET | `/admin/orders/{order:order_number}` | `admin.orders.show` | `show` |
| POST | `/admin/orders/{order}/status` | `admin.orders.status` | `updateStatus` |
| POST | `/admin/orders/{order}/payment` | `admin.orders.mark-paid` | `markPaid` |
| PUT | `/admin/orders/{order}/note` | `admin.orders.note` | `updateNote` |

Static `Route::view()` order routes are removed.

## 9. Security requirements
- Behind `auth` + `admin` (staff allowed, ADR-012). Customers get 403.
- Status and payment changes lock the order row; transitions are checked on the server.

## 10. Edge cases
- Two staff change the same order at once → the second sees "can't move from X to Y"
- Ship without courier → refused
- Mark paid twice / on a cancelled order → refused
- Cancel a paid order → refund row
- Return after delivery → stock back with reason `returned`

## 11. Acceptance criteria
- [x] List with tabs + counts, search, date range, pagination
- [x] Detail with items, totals, history (who + note), customer, address, payment
- [x] Allowed transitions only; history row each time
- [x] Courier + tracking; admin note; mark paid
- [x] Cancel/return restock + payment settled
- [x] Pending badge

## 12. Multi-vendor impact
None. Per-vendor shipments would later move courier fields to sub-orders (database.md §6).

## 13. Open questions (decided — user: "all approved", 2026-10-07)
- [x] Transition map as in §3.2 (no skipping steps).
- [x] Returned items go back to stock; damaged ones are written off in Inventory.
- [x] Coupon use released on cancel, kept on return.
- [x] "Send SMS update" checkbox from the prototype dropped until F-029.
- [x] Payment filter and Export button from the prototype dropped (COD only; export not planned).
