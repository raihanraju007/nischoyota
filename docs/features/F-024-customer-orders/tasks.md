# F-024: Customer orders & tracking — Tasks

Status: `[ ]` todo · `[~]` doing · `[x]` done. Tick items as they finish. Commit after each green group.

## Database
- [x] No new tables; `PaymentStatus::Cancelled`; `order_items.image_url` snapshot added to the (uncommitted) F-023 migration

## Backend
- [x] `InventoryService::restockForCancelledOrder()` (reverses the order's `order_placed` movements), `CouponService::releaseUse()`
- [x] `OrderStatus::isCancellableByCustomer()`, `progressSteps()`, `stepTitle()`, `customerFilters()`; `Order::estimatedDeliveryLabel()`
- [x] `OrderRepository` customer queries + row lock; cancelled orders excluded from the coupon limit
- [x] `CustomerOrderService` (list, recent, find, track, cancel, buy again, timeline), `OrderException`, `OrderCancelled`, `BuyAgainResult`, `TimelineStep`
- [x] `Account\OrderController`, `TrackOrderController`, `TrackOrderRequest`, routes; static order routes removed
- [x] Account dashboard recent orders

## Frontend
- [x] `account/orders/index` (chips, table, pagination, empty state; `empty.blade.php` removed), `account/orders/show` (timeline, cancel modal, buy again), `orders/track`
- [x] `x-order.status` from the enum (+ `status-returned` style), `x-account.order-table` from orders

## Tests
- [x] List, filter, pagination, empty state; detail + timeline; other customer's order 404
- [x] Cancel (stock, coupon, history, payment, event); pre-order stock; refused after processing / twice; confirmed allowed
- [x] Buy again (all, partial, none); dashboard; coupon limit ignores cancelled orders
- [x] Tracking (format-insensitive, wrong phone, validation)

## Verification
- [x] Narrow tests pass
- [x] `vendor/bin/pint --dirty --format agent`
- [ ] Manual browser check (Chrome extension not connected)

## Documentation
- [x] `feature.md` status → Done
- [x] Roadmap (Milestone M3), `docs/memory.md`, `docs/architecture.md`, features index
