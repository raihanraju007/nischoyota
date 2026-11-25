# F-024: Customer orders & tracking

- **Status:** Done
- **Module:** `app/Modules/Order`
- **Phase:** 1
- **Depends on:** F-023

## 1. Objective
Customers see their orders and where each one is, cancel an order that has not been packed yet, and order the same things again. Guests track an order with its number and phone.

## 2. User stories
- As a **customer**, I want a list of my orders and each order's details and progress.
- As a **customer**, I want to cancel an order before it is packed, and buy the same items again.
- As a **guest**, I want to track my order with the order number and my phone.

## 3. Business rules
1. `/account/orders` lists the signed-in customer's orders, newest first, 10 per page, with status chips: All, Pending, Confirmed, Processing, Shipped, Delivered, Cancelled. Empty → the empty state.
2. Order detail (`/account/orders/{number}`) shows status, date, payment method, estimated delivery (placed date + zone min–max days, Dhaka time), items, totals, customer + address snapshots, and the timeline. Another customer's order → 404.
3. **Timeline:** Placed → Confirmed → Processing → Shipped → Delivered, each done step with the time from the status history. A cancelled order shows Placed → Cancelled; a returned one ends with Returned.
4. **Cancel:** only while `pending` or `confirmed` (`OrderStatus::isCancellableByCustomer()`). In one transaction: status → `cancelled`, history row (by the customer, note "Cancelled by customer"), order and payment rows → payment `cancelled`, stock given back exactly as it was taken (`InventoryService::restockForCancelledOrder()`: reverses the order's `order_placed` movements as `order_cancelled`), coupon use released (`CouponService::releaseUse()`). `OrderCancelled` is dispatched after commit (F-029). Cancelled orders do not count towards a customer's coupon limit.
5. **Buy again:** adds each item's variant with its quantity to the cart (F-021 rules); items that can no longer be bought are skipped and named. → cart with a message.
6. **Guest tracking** (`/track-order`): order number (with or without `#`, any case) + phone (normalised) must both match; the result shows timeline, items, totals and delivery address. Wrong pair → "We couldn't find an order with that number and phone." Throttled (10/min). The phone is sent by POST, never in the URL.
7. Account dashboard shows the 3 most recent orders.

## 4. Scope
- `OrderRepository` queries, `OrderService` (list, find, cancel, buyAgain, track, timeline), `OrderException`, `OrderCancelled`, `BuyAgainResult`
- `InventoryService::restockForCancelledOrder()`, `CouponService::releaseUse()`, `PaymentStatus::Cancelled`
- `Account\OrderController` (index, show, cancel, buyAgain), `TrackOrderController` (show, track), `TrackOrderRequest`
- Views: `account/orders/index` (empty state included), `account/orders/show`, `orders/track`, dashboard recent orders; `x-order.status` from the enum, `x-account.order-table` with links

## 5. Out of scope
- Admin order management (F-025), invoices/print (F-025b), returns flow, notifications (F-029), cancellation reasons

## 7. Database requirements
No new tables. `payments.status` / `orders.payment_status` gain the value `cancelled`.

## 8. Routes / API
| Method | URI | Name | Controller@action | Auth |
|---|---|---|---|---|
| GET | `/account/orders` | `account.orders` | `OrderController@index` | `auth`, `active` |
| GET | `/account/orders/{orderNumber}` | `account.orders.show` | `OrderController@show` | own order |
| POST | `/account/orders/{orderNumber}/cancel` | `account.orders.cancel` | `OrderController@cancel` | own order |
| POST | `/account/orders/{orderNumber}/buy-again` | `account.orders.buy-again` | `OrderController@buyAgain` | own order |
| GET | `/track-order` | `orders.track` | `TrackOrderController@show` | public |
| POST | `/track-order` | `orders.track.lookup` | `TrackOrderController@lookup` | public, `throttle:10,1` |

Static `account.orders.empty` and the fixed `account.orders.show` routes are removed.

## 9. Security requirements
- Orders are always loaded scoped to the signed-in user, or by number + phone together.
- Cancel/buy again are POST with CSRF; the status check happens under a row lock.

## 10. Edge cases
- Cancel a shipped / delivered / already cancelled order → refused, nothing changes
- Double cancel → second one refused
- Pre-order item: only the stock actually taken is given back
- Buy again with a deleted or sold-out variant → skipped and named
- Track with right number and wrong phone → not found

## 11. Acceptance criteria
- [x] List with pagination and status filter; empty state
- [x] Detail with timeline; other customer's order 404
- [x] Cancel restores stock, releases coupon, records history; refused after processing
- [x] Buy again adds available items, reports the rest
- [x] Guest tracking by number + phone
- [x] Dashboard recent orders

## 12. Multi-vendor impact
None now; per-vendor shipments would add per-item status later.

## 13. Open questions (decided, "all approved")
- [x] Release the coupon use on cancel → yes; cancelled orders also don't count for the per-customer limit.
- [x] "Out for Delivery" step in the prototype → dropped (no such status).
- [x] Tracking result by POST (phone not in URLs, privacy).
