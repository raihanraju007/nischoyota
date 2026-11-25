# F-023: Checkout (Cash on Delivery) — Tasks

Status: `[ ]` todo · `[~]` doing · `[x]` done. Tick items as they finish. Commit after each green group.

## Database
- [x] Migrations `orders`, `order_items`, `order_status_histories`, `payments`
- [x] Models `Order`, `OrderItem`, `OrderStatusHistory`, `Payment`; enums `OrderStatus`, `PaymentMethod`, `PaymentStatus`
- [x] Factories `OrderFactory`, `OrderItemFactory`

## Backend
- [x] Other modules (through their services): `InventoryService::deductForOrder()`, `CouponService::recordUse()`, `LocationService::findUpazilaWithParents()`, `AddressService::findForUser()`, `OtpPurpose::CheckoutPhone`; `CartLine` gains `productId`, `sku`, `allowsPreorder`
- [x] Data `CheckoutData`, `NewAddressData`, `ShippingAddress`, `CheckoutTotals`; `CheckoutException`; `OrderPlaced` (after commit)
- [x] `OrderRepository`, `CheckoutService` (totals, page options, phone code), `OrderService::place()` (one transaction, locks in variant id order)
- [x] `PlaceOrderRequest`, `CheckoutController` (show, store, verify, resend, success, delivery quote), routes; static checkout routes removed
- [x] `Order` module provider registered

## Frontend
- [x] `checkout/index` (saved addresses, new address with `<x-address-fields>`, COD, terms, live summary), `checkout/verify`, `checkout/success`
- [x] `order/items` + `order/totals`: paisa mode, summary hooks; payment-option selection scoped per radio group
- [x] JS: delivery line, total and expected total follow the chosen address

## Tests
- [x] Signed-in order (all records, stock movement, cart cleared, event, success page)
- [x] Saved address, new address saved, someone else's address refused
- [x] Guest phone code (wrong code, right code, no second code in the session)
- [x] Stock gone before submit; sold out during placement → full rollback
- [x] Coupon stopped applying; coupon use counted; per-customer limit
- [x] Price changed after add-to-cart; area without delivery; double submit; pre-order
- [x] Validation; guest cannot send address_id; delivery quote JSON; success without order

## Verification
- [x] Narrow tests pass
- [x] `vendor/bin/pint --dirty --format agent`
- [ ] Manual browser check (Chrome extension not connected)

## Documentation
- [x] `feature.md` status → Done
- [x] Roadmap `docs/tasks.md`, `docs/memory.md`, `docs/architecture.md`, features index
