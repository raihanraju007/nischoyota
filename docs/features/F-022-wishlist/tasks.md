# F-022: Wishlist — Tasks

Status: `[ ]` todo · `[~]` doing · `[x]` done. Tick items as they finish. Commit after each green group.

## Database
- [x] Migration `wishlist_items`
- [x] Model `WishlistItem` + factory

## Backend
- [x] Catalog: `ProductCardData::productId`, `ProductListingService::cardsFor()`, `listedProductIds()`; home cache key bumped
- [x] `WishlistRepository`, `WishlistService`, `WishlistException`
- [x] `CurrentWishlist` (saved ids per request) + `WishlistComposer`; shell composer count
- [x] `WishlistController` + routes; remove static wishlist routes

## Frontend
- [x] `wishlist/index`, `wishlist/empty` from data
- [x] Heart forms on product card and product page; Move to cart on the wishlist page
- [x] JS: fetch toggle, badge, 401 → login, card removed on `/wishlist`

## Tests
- [x] Feature: toggle (redirect + JSON), guest, listed-only
- [x] Feature: page, unlisted hidden, order, other users
- [x] Feature: move to cart (success, failure)
- [x] Feature: count and active hearts

## Review fixes
- [x] Double tap: `createOrFirst` (no unique-constraint 500)
- [x] Test: blocked customer
- [x] JS: expired session (419) reloads the page; 429 on add shows a toast

## Verification
- [x] Narrow tests pass
- [x] `vendor/bin/pint --dirty --format agent`
- [ ] Manual browser check (Chrome extension not connected)

## Documentation
- [x] `feature.md` status → Done
- [x] Roadmap `docs/tasks.md`, `docs/memory.md`, `docs/architecture.md`, features index
