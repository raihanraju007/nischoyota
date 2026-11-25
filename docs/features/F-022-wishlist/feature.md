# F-022: Wishlist

- **Status:** Done
- **Module:** `app/Modules/Shopping`
- **Phase:** 1
- **Depends on:** F-017, F-014, F-021

## 1. Objective
Signed-in customers save products they like with the heart button, see them on `/wishlist`, and move them to the cart.

## 2. User stories
- As a **customer**, I want to tap the heart on a product to save it, and tap again to remove it.
- As a **customer**, I want one page with everything I saved, so I can buy it later.
- As a **guest**, I want to be told to log in when I tap the heart, and come back to where I was.

## 3. Business rules
1. The wishlist belongs to a **signed-in, active** customer (`auth` + `active`). Guests who tap a heart are sent to login (JSON: 401 → the script goes to the login page; afterwards the customer returns to the page they were on).
2. A wishlist entry is a **product** (not a variant): one row per user + product.
3. The heart **toggles**: saved → removed, not saved → saved. Only listed products (active, active category) can be saved; removing always works.
4. `/wishlist` shows saved products that are still listed, newest saved first, as normal product cards (live price, stock, rating). Saved products that are no longer listed are hidden (the row is kept; it shows again if the product comes back). Deleting a product deletes its rows.
5. **Move to cart** (single-variant products): adds 1 of the variant to the cart (F-021 rules: stock, max 99) and removes it from the wishlist. If the add fails, the product stays in the wishlist and the reason is shown. Products with options show "Choose Options" (link to the product page).
6. The **wishlist count** in the header and bottom nav = saved products that are still listed; hidden at 0. Guests see no count.
7. Hearts on every product card and on the product page show as active for saved products.
8. No limit on the number of saved products.

## 4. Scope
- `wishlist_items` migration, `WishlistItem` model + factory
- `WishlistRepository`, `WishlistService` (toggle, moveToCart, productIdsFor, listedCount, cards)
- Catalog: `ProductListingService::cardsFor(ids)`, `listedProductIds(ids)`; `ProductCardData::productId`
- `WishlistController` (index, toggle, moveToCart), routes behind `auth` + `active`
- `CurrentWishlist` (saved ids once per request) + `WishlistComposer` for product cards; shell composer count
- Views: `wishlist/index`, `wishlist/empty` from data; heart forms on `product-card` and `products/show`
- JS: heart toggles with `fetch` (count, active state, toast; 401 → login); on `/wishlist` a removed card disappears

## 5. Out of scope
- Guest wishlists, sharing a wishlist, price-drop / back-in-stock alerts, saving a specific variant

## 6. UI requirements
- `resources/views/wishlist/index.blade.php`, `wishlist/empty.blade.php` (prototype pages), heart `wishlist-btn` on cards, `icon-btn-outline` heart on the product page
- States: empty page, saved (red heart), toasts "Added to wishlist" / "Removed from wishlist"

## 7. Database requirements
| Table | Columns (key ones) | Notes |
|---|---|---|
| `wishlist_items` | `user_id` FK (cascade), `product_id` FK (cascade), timestamps | unique (`user_id`, `product_id`); index `product_id` |

(The roadmap said `wishlists`; one row per saved product reads better as `wishlist_items`.)

## 8. Routes / API
| Method | URI | Name | Controller@action | Auth |
|---|---|---|---|---|
| GET | `/wishlist` | `wishlist` | `WishlistController@index` | `auth`, `active` |
| POST | `/wishlist/{product}` | `wishlist.toggle` | `WishlistController@toggle` (JSON or redirect) | `auth`, `active`, `throttle:60,1` |
| POST | `/wishlist/{product}/move-to-cart` | `wishlist.move` | `WishlistController@moveToCart` | `auth`, `active` |

`{product}` is the product id. The static `wishlist.empty` route is removed.

## 9. Security requirements
- Customers only see and change their own wishlist (queries scoped to the signed-in user).
- Toggle and move validated server-side: product must be listed to save; the variant for "move" is the product's only active variant, never a client value.
- CSRF on every POST; toggle throttled.

## 10. Edge cases
- Guest taps heart → login, then back to the page
- Save an unlisted / unknown product → error, nothing saved
- Remove a product that was archived after saving → works
- Product archived after saving → hidden from page and count; back when re-listed
- Move a sold-out product → error, stays in wishlist
- Move a product with options → not offered (Choose Options)
- Double tap → toggles twice (saved, then removed)

## 11. Acceptance criteria
- [x] Customer toggles a product on and off (redirect and JSON)
- [x] Guest → 401 JSON / redirect to login; `/wishlist` needs login
- [x] Only listed products can be saved
- [x] Page lists listed saved products newest first; unlisted hidden
- [x] Move to cart adds and removes; failure keeps it and shows the reason
- [x] Header count; hearts active on cards and product page
- [x] Users cannot see each other's wishlists

## 12. Multi-vendor impact
None: entries point at products, which will carry a vendor in Phase 2.

## 13. Open questions
- [x] Product or variant? → **product** (the heart is on the card, before a variant is chosen).
- [x] Guest wishlist? → no; guests log in (roadmap).
- [x] Unlisted saved products? → hidden, row kept.
- [x] Limit? → none.
