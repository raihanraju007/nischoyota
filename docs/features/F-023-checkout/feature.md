# F-023: Checkout (Cash on Delivery)

- **Status:** Done
- **Module:** `app/Modules/Order`
- **Phase:** 1
- **Depends on:** F-008, F-009, F-011, F-014, F-015, F-021

## 1. Objective
Turn the cart into a Cash on Delivery order, for guests and signed-in customers, with every amount recalculated on the server and stock taken in the same transaction.

## 2. User stories
- As a **customer**, I want to pick a saved address or type a new one, see the delivery charge and total, and place the order.
- As a **guest**, I want to order without an account, confirming my phone with a code.
- As the **store**, I want every order to have correct prices, stock taken, the coupon counted, and a clear history.

## 3. Business rules
1. Checkout needs a cart that `canCheckout()` (F-021); otherwise → `/cart` with a message.
2. **Customer:** name (≤ 100), phone (BD mobile, normalised), email (optional). Signed-in customers are pre-filled from their profile.
3. **Address:** a signed-in customer picks a saved address (default pre-selected) or enters a new one; a guest enters one. New address: division → district → upazila (must match), area (optional), address line, postal code (4 digits, optional). "Save this address" (signed-in, new address) adds it to the address book when there is room (F-015 limit 10); a full book does not block the order.
4. **Payment:** Cash on Delivery only (`PaymentMethod::CashOnDelivery`). Terms must be accepted.
5. **The server recalculates everything** from the cart at submit time: live variant prices, coupon (`CouponService::apply` with this customer's earlier uses: by `user_id`, or by phone for guests), delivery (`DeliveryChargeService::quoteFor(upazila, subtotal)`; free delivery on the subtotal **before** the coupon, as in F-021), total = subtotal − discount + delivery.
6. **Price check:** the form carries the total the customer saw. If the recalculated total differs (a price, coupon or delivery changed), nothing is placed and checkout shows "Prices changed — please check your order" with the new totals.
7. **One transaction:** order (number, customer + address snapshots, totals, coupon), items (product name, variant label, SKU, unit price, quantity, line total snapshots), status history row (`pending`), payment row (COD, `pending`, total), stock taken through `InventoryService::deductForOrder()` (movement `order_placed` with the order id), coupon use counted (`CouponService::recordUse`, atomic against the usage limit), cart cleared. Any failure rolls everything back.
8. **Stock at submit:** if a variant no longer has enough stock (someone else bought it), the order fails with "Sorry, {product} just sold out. Only N left." Pre-order products take what is in stock and order the rest (stock never goes below 0).
9. **Unavailable area:** an upazila without an active delivery zone → "We don't deliver to this area yet."
10. **Guest phone (D2):** a guest must confirm the phone with an SMS code (`OtpPurpose::CheckoutPhone`) before the order is placed. The checkout input is kept in the session; after the right code the order is placed. A phone confirmed in this session is not asked again. Signed-in customers (phone verified at registration) skip this.
11. **Order number:** `ORD-` + (10000 + id), e.g. `ORD-10025`.
12. **Double submit:** each checkout page has a one-time token; a second submit of the same page does not create a second order (it goes to the success page of the order just placed).
13. **Success page** shows the order just placed in this session (number, date, payment, total, delivery estimate); without one → home.
14. After commit, `OrderPlaced` is dispatched (F-029 sends notifications).

## 4. Scope
- `Order` module: `orders`, `order_items`, `order_status_histories`, `payments` + models, factories; enums `OrderStatus`, `PaymentMethod`, `PaymentStatus`
- `OrderRepository`, `OrderService::place()`, `CheckoutService` (page data, delivery preview), `CheckoutException`, `OrderPlaced` event
- `PlaceOrderRequest`, `CheckoutController` (show, store, verify form + store + resend, success, delivery quote JSON)
- Other modules: `InventoryService::deductForOrder()`, `CouponService::recordUse()`, `LocationService::findUpazilaWithParents()`, `OtpPurpose::CheckoutPhone`, `AddressService::findForUser()`
- Views: `checkout/index`, `checkout/verify`, `checkout/success` from data; JS delivery preview on area change

## 5. Out of scope
- Online payment, order pages for customers (F-024) and admin (F-025), notifications (F-029), delivery note, `sold_count` updates (counted on delivery later)

## 7. Database requirements
| Table | Columns (key ones) | Notes |
|---|---|---|
| `orders` | `order_number` unique nullable, `user_id` nullable FK (null on delete), `status`, customer name/phone/email, `division_id`/`district_id`/`upazila_id` + their names, `area`, `address_line`, `postal_code`, `delivery_zone_id` nullable FK + `delivery_zone_name`, `subtotal`, `discount_total`, `delivery_charge`, `total`, `coupon_id` nullable FK + `coupon_code`, `payment_method`, `payment_status`, `placed_at` | index `status`, `customer_phone`, `placed_at`; FKs to locations restrict |
| `order_items` | `order_id` FK cascade, `product_id`/`product_variant_id` nullable FK (null on delete), `product_name`, `variant_label`, `sku`, `unit_price`, `quantity`, `line_total`, `is_preorder` | |
| `order_status_histories` | `order_id` FK cascade, `from_status` nullable, `to_status`, `user_id` nullable, `note`, `created_at` | |
| `payments` | `order_id` FK cascade, `method`, `status`, `amount`, `transaction_reference` nullable, `paid_at` nullable | |

## 8. Routes / API
| Method | URI | Name | Controller@action | Auth |
|---|---|---|---|---|
| GET | `/checkout` | `checkout` | `CheckoutController@show` | public |
| POST | `/checkout` | `checkout.store` | `CheckoutController@store` | public, `throttle:10,1` |
| GET | `/checkout/verify` | `checkout.verify` | `CheckoutController@verifyForm` | public (pending checkout) |
| POST | `/checkout/verify` | `checkout.verify.store` | `CheckoutController@verify` | public, `throttle:10,1` |
| POST | `/checkout/verify/resend` | `checkout.verify.resend` | `CheckoutController@resend` | public |
| GET | `/checkout/success` | `checkout.success` | `CheckoutController@success` | public (session) |
| GET | `/checkout/delivery-quote` | `checkout.delivery-quote` | `CheckoutController@deliveryQuote` (JSON) | public |

## 9. Security requirements
- Never trust browser prices/totals/stock; only `expected_total` is compared, never used.
- A saved address must belong to the signed-in user.
- Guest phone confirmed by OTP; OTP send limits from F-014.
- CSRF, throttles, one-time checkout token.

## 10. Edge cases
- Stock runs out between cart and submit → error, nothing saved, stock unchanged
- Coupon invalid at submit (expired, used up, per-customer limit) → error with the reason
- Price changed after add-to-cart / after the page loaded → "Prices changed"
- Cart empty or with issues → back to cart
- Area without delivery → error
- Double submit → one order
- Guest: wrong code → error; phone already confirmed in session → no second code
- Address book full + "save" → order placed, address not saved

## 11. Acceptance criteria
- [x] Signed-in checkout with a saved address and with a new address (saved when asked)
- [x] Guest checkout with OTP
- [x] Order, items, history, payment, stock movements, coupon use, cart cleared
- [x] Stock running out mid-checkout, invalid coupon, price change, unknown area → nothing saved
- [x] Double submit → one order; order number format
- [x] Success page; `OrderPlaced` dispatched

## 12. Multi-vendor impact
Items snapshot product and variant ids; Phase 2 can split an order per vendor by item. Delivery stays in `DeliveryChargeService`. No vendor fields now.

## 13. Open questions (decided, "all approved")
- [x] Price changed between page and submit → reject and show new totals (not silent).
- [x] Invalid coupon at submit → reject with the reason (customer removes it in the cart).
- [x] Pre-order stock → take what is there, rest is pre-ordered (`is_preorder` on the item).
- [x] Guest coupon per-customer limit → counted by phone.
- [x] Full address book → order still placed.
