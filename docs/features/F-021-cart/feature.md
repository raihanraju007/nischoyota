# F-021: Cart

- **Status:** Done
- **Module:** `app/Modules/Shopping`
- **Phase:** 1
- **Depends on:** F-017, F-014 (also uses F-011 coupons, F-009 delivery, F-015 addresses)

## 1. Objective
Customers (guests and signed-in) collect products in a real cart, see live prices, stock problems, a coupon preview and a delivery estimate, and keep the cart when they sign in. Checkout (F-023) reads this cart.

## 2. User stories
- As a **guest**, I want to add products to a cart without an account, and keep it when I sign in.
- As a **customer**, I want my cart saved to my account so it is there on my next visit.
- As a **customer**, I want to change quantities, remove items and clear the cart.
- As a **customer**, I want to know before checkout if an item is sold out, unavailable or its price changed.
- As a **customer**, I want to try a coupon and see the delivery charge before checkout.

## 3. Business rules
1. **One cart per owner.** A signed-in user has one cart (`carts.user_id`). A guest's cart is found by a random token kept in the session (`carts.guest_token`); the token and cart row are created on the first add, not on page views.
2. A cart line is one **variant** (ADR-007, D5). Adding a variant already in the cart increases its quantity.
3. Quantity per line: 1 – 99 (`CartService::MAX_LINE_QUANTITY`).
4. **Stock check on every add/update:** a variant can be added only when it is active, its product is `active` and its category is active. The new quantity must not exceed `stock_quantity` unless the product allows pre-order. Errors: "This product is not available.", "This item is out of stock.", "Only :count left in stock.", "You can buy at most 99 of one item."
5. **Prices are always read live from the variant**; the cart never totals a stored price. Each line stores `added_price` only to show "Price changed from ৳X" when the live price differs.
6. **Line states** (`CartLineState`, worked out on every read):
   - `Available`: in stock for the quantity
   - `Preorder`: not enough stock, product allows pre-order (buyable)
   - `NotEnoughStock`: stock > 0 but less than the quantity
   - `OutOfStock`: stock 0, no pre-order
   - `Unavailable`: variant inactive, product not `active`, or category hidden
   Only `Available` / `Preorder` lines count in the subtotal. Any other line blocks "Proceed to Checkout" until the customer fixes or removes it.
7. **Merge on sign-in** (any `Login` event: login, register → verify, reset, remember-me): guest lines move into the user's cart; a variant in both gets the summed quantity, capped at 99 (stock is checked again on read, rule 6). The guest's coupon is kept if the user's cart has none. The guest cart is deleted. Logging out does not empty the user's cart; the guest starts a new one.
8. **Coupon preview:** "Apply" runs `CouponService::apply(code, subtotal)`; on success the code is saved on the cart, on failure the reason is shown and nothing is saved. On every cart view the saved code is checked again: if it no longer applies (e.g. subtotal fell below the minimum) the reason is shown and the discount is 0, but the code stays so it applies again later. The per-customer usage limit is checked at checkout (F-023), not here.
9. **Delivery estimate:** signed-in user with a default address → `DeliveryChargeService::quoteFor(upazila, subtotal)` (subtotal **before** coupon decides free delivery; F-023 must use the same rule). Otherwise the delivery line says "Calculated at checkout". When a free-delivery threshold is set and the subtotal is below it, show "Add ৳X more for free delivery".
10. Total = subtotal − coupon discount + delivery (delivery 0 when not known yet).
11. **Cart count** in the header, bottom nav and drawer = number of lines (not the sum of quantities); hidden at 0.
12. Guest carts not updated for 30 days are pruned (`model:prune`, daily).

## 4. Scope
- New `Shopping` module: `carts`, `cart_items` migrations, `Cart`, `CartItem` models + factories, `CartLineState` enum
- `CartRepository`, `CartService` (add / update / remove / clear / coupon / merge / contents / totals), DTOs `CartOwner`, `CartLine`, `CartContents`, `CartTotals`, `CartException`
- `CurrentCart` (HTTP edge): resolves the owner from the signed-in user or the session token, caches the contents for the request
- `MergeGuestCartOnLogin` listener on `Illuminate\Auth\Events\Login`
- Catalog: `ProductVariantService::findForCart()` (variant + product + category); product cards get `quickAddVariantId` (single-variant products add straight from the card; products with options link to the product page)
- Views: `cart/index`, `cart/empty` (real best sellers), cart drawer, product page buy form (POST, Buy Now → checkout), product card button, header/bottom-nav counts
- JS: add-to-cart via `fetch` (JSON: message, count, drawer HTML) with a normal form POST fallback; qty steppers inside cart forms submit the form

## 5. Out of scope
- Checkout, order creation, stock reservation (F-023)
- Per-customer coupon usage check (F-023)
- Wishlist and "move to wishlist" (F-022)
- Delivery estimate by picking an area on the cart page (checkout chooses the address)
- Saving guest carts beyond the session lifetime (no long-lived cookie)

## 6. UI requirements
- Pages / views: `resources/views/cart/index.blade.php`, `cart/empty.blade.php`, `components/layout/cart-drawer.blade.php`, `products/show.blade.php`, `components/product-card.blade.php`, `components/order/totals.blade.php` (gains a paisa mode and "Calculated at checkout")
- States: empty (empty page with best sellers / empty drawer), line issues (note under the line, checkout disabled), coupon error, success toasts

## 7. Database requirements
| Table | Columns (key ones) | Notes |
|---|---|---|
| `carts` | `user_id` nullable unique FK (cascade), `guest_token` string(64) nullable unique, `coupon_code` string(30) nullable, timestamps | index `updated_at` (pruning) |
| `cart_items` | `cart_id` FK (cascade), `product_variant_id` FK (cascade), `quantity` unsigned smallint, `added_price` unsigned int (paisa) | unique (`cart_id`, `product_variant_id`) |

## 8. Routes / API
| Method | URI | Name | Controller@action | Auth |
|---|---|---|---|---|
| GET | `/cart` | `cart` | `CartController@index` | public |
| DELETE | `/cart` | `cart.clear` | `CartController@clear` | public |
| POST | `/cart/items` | `cart.items.store` | `CartItemController@store` (JSON or redirect) | public, `throttle:60,1` |
| PATCH | `/cart/items/{item}` | `cart.items.update` | `CartItemController@update` | public, own cart only |
| DELETE | `/cart/items/{item}` | `cart.items.destroy` | `CartItemController@destroy` | public, own cart only |
| POST | `/cart/coupon` | `cart.coupon.store` | `CartCouponController@store` | public, `throttle:20,1` |
| DELETE | `/cart/coupon` | `cart.coupon.destroy` | `CartCouponController@destroy` | public |

The static `cart.empty` route is removed (the empty state is part of `/cart`).

## 9. Security requirements
- A line can only be changed through its owner's cart: another cart's item id → 404.
- Never trust client prices, totals or stock: everything is read from the variant on the server.
- Quantity and coupon code validated in FormRequests; CSRF on every write; throttled add and coupon routes.
- Guest token is random (64 chars) and lives only in the session.

## 10. Edge cases
- Add a draft / archived / hidden-category / inactive variant → error, nothing added
- Add more than stock (no pre-order) → "Only N left"; pre-order product → allowed
- Stock drops after adding → line shows the issue, excluded from subtotal, checkout disabled
- Price changes after adding → live price used, "Price changed" note
- Variant deleted by admin → line disappears (cascade)
- Product archived after adding → line `Unavailable`
- Coupon valid when applied, subtotal later below minimum → reason shown, discount 0, code kept
- Guest signs in with an existing user cart holding the same variant → quantities summed, capped at 99
- Another cart's item id in update/remove → 404
- Guest who never added anything → no cart row is created

## 11. Acceptance criteria
- [x] Guest and user can add, update, remove, clear; count shown in header
- [x] Adding the same variant twice sums quantities
- [x] Stock / availability / max-quantity errors on add and update
- [x] Line states and subtotal follow rule 6; checkout disabled with issues
- [x] Live price used; price-change note shown
- [x] Guest cart merges into the user cart on login (and is deleted)
- [x] Coupon apply / remove / re-check on view
- [x] Delivery estimate from the default address; "Calculated at checkout" otherwise; free-delivery hint
- [x] Another owner's item → 404
- [x] JSON add returns message, count and drawer HTML
- [x] Product cards: quick add for single-variant products, link for products with options

## 12. Multi-vendor impact
Lines point at variants, which will belong to a vendor through their product; a Phase 2 cart can group lines by vendor without a schema change. Delivery stays in `DeliveryChargeService` (per-vendor shipments extend it there).

## 13. Open questions
- [x] Badge counts lines or quantities? → **lines** (matches the prototype "3 items").
- [x] Guest cart lifetime? → the session (120 min); a long-lived cookie can come later.
- [x] Free delivery decided on subtotal before or after coupon? → **before**; F-023 uses the same rule.
- [x] Max quantity per line? → 99 (the existing quantity control's limit).
