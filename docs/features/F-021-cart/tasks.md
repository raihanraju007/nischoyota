# F-021: Cart — Tasks

Status: `[ ]` todo · `[~]` doing · `[x]` done. Tick items as they finish. Commit after each green group.

## Database
- [x] Migrations `carts`, `cart_items`
- [x] Models `Cart` (prunable guest carts), `CartItem`: relations, casts, `$fillable`
- [x] Factories `CartFactory` (states `forUser`, `forGuest`), `CartItemFactory`

## Backend
- [x] `Shopping` module + provider (routes, Login listener) registered in `bootstrap/providers.php`
- [x] Enum `CartLineState`; DTOs `CartOwner`, `CartLine`, `CartContents`, `CartTotals`; `CartException`
- [x] Catalog: `ProductVariantService::findForCart()` + repository query
- [x] `CartRepository`
- [x] `CartService`: add, updateQuantity, remove, clear, applyCoupon, removeCoupon, mergeGuestCart, contentsFor, totalsFor
- [x] `CurrentCart` (owner from user / session token, contents cached per request)
- [x] `MergeGuestCartOnLogin` listener
- [x] FormRequests `AddToCartRequest`, `UpdateCartItemRequest`, `ApplyCouponRequest`
- [x] Controllers `CartController`, `CartItemController`, `CartCouponController`; routes; remove static cart routes
- [x] `StorefrontShellComposer` cart count; drawer composer
- [x] Prune schedule

## Frontend
- [x] `cart/index` from data (lines, issues, price change, qty forms, remove, clear modal, coupon, totals, delivery hint)
- [x] `cart/empty` with real best sellers
- [x] Cart drawer from data
- [x] `order/totals` paisa mode + "Calculated at checkout"
- [x] Product page buy form → POST (Add to Cart, Buy Now)
- [x] Product card: quick add form / "Choose Options" link (`ProductCardData::quickAddVariantId`)
- [x] JS: fetch add-to-cart (badges, drawer, toast), qty steppers submit cart forms

## Tests
- [x] Feature: add / update / remove / clear (guest + user)
- [x] Feature: validation and stock / availability errors
- [x] Feature: another cart's item → 404
- [x] Feature: line states, live price, price-change note, checkout disabled
- [x] Feature: merge on login
- [x] Feature: coupon apply / remove / re-check
- [x] Feature: delivery estimate + free-delivery hint
- [x] Feature: JSON add, header count, product card quick add
- [x] Unit: line state rules

## Verification
- [x] Narrow tests pass
- [x] `vendor/bin/pint --dirty --format agent`
- [ ] Manual browser check (Chrome extension not connected; HTTP smoke test of add → cart page passed)

## Documentation
- [x] `feature.md` status → Done
- [x] Roadmap `docs/tasks.md` updated
- [x] `docs/memory.md` updated
- [x] `docs/architecture.md` modules table (new module)
