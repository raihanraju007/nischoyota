# F-019: Home page

- **Status:** Done
- **Module:** `app/Modules/Catalog`
- **Phase:** 1
- **Depends on:** F-016

## 1. Objective
The home page shows real departments and products instead of prototype data, so customers land on what the store actually sells.

## 2. User stories
- As a **customer**, I want to see the store's departments, featured products, today's deals, new arrivals and best sellers on the home page.
- As an **admin**, I want products I mark as **Featured** to appear on the home page.

## 3. Business rules
1. Only listed products (active, in an active category), as in F-016. Cards use `ProductCardData` (same price, old price, badge and stock rules as the shop).
2. **Shop by Category:** active departments that have listed products (their own or their subcategories'), in menu order, max 7, each with its image and "N Products". An 8th **Deals** tile shows "Up to X% OFF" when there are deals.
3. **Featured Products:** up to 8 listed products marked Featured, newest published first.
4. **Today's Deals:** up to 4 discounted products, biggest discount first (then best selling). Header shows "Up to X% OFF" and a countdown to midnight Bangladesh time (same as the Deals page).
5. **New Arrivals:** 4 newest published products. **Best Sellers:** 4 products with the highest `sold_count` (then newest).
6. A product section with no products is not shown.
7. **Promo banners** stay static (copy and images) and link to the Home & Living (`home-living`) and Fashion (`fashion`) category pages; if that department is not listed, the link goes to the Shop.
8. Hero, trust strip and **customer reviews** stay static. The reviews become real in F-027.
9. **Caching:** the sections are cached for 10 minutes (`Cache::remember`, stored as arrays because `cache.serializable_classes` is off). Admin changes can take up to 10 minutes to show. The countdown is computed per request.

## 4. Scope
- `HomeController@index` on `/` (replaces `Route::view('/', 'home')`), `HomePageService`, `HomePageData`
- `ProductListingRepository`: featured, biggest deals, newest queries; department tiles with image
- `ProductCardData::toArray()/fromArray()` for the cache
- Shared `ProductListingService::dealHoursLeft()` used by the Deals page and home
- `home.blade.php`, `<x-category-card>` (real image URL), `<x-promo-banners>` (links)

## 5. Out of scope
- Banners editable in Settings, hero slider, personalised sections, real reviews (F-027)
- Clearing the cache on product/stock changes (TTL only)

## 6. UI requirements
- Page: `resources/views/home.blade.php` (prototype: `nischoyota-frontend/index.html`)
- States: empty store shows hero, trust strip and reviews only; each empty product section hidden

## 7. Database requirements
None (uses `products.is_featured`, `sold_count`, `published_at`, `max_discount_percent`; `categories.image`).

## 8. Routes / API
| Method | URI | Name | Controller@action | Auth |
|---|---|---|---|---|
| GET | `/` | `home` | `Catalog\HomeController@index` | public |

## 9. Security requirements
- Public, read-only; no input.

## 10. Edge cases
- No products / no deals / nothing featured → those sections and the Deals tile are hidden
- Department with products only in subcategories → counted
- Inactive department or one without listed products → no tile
- Category without image → placeholder image
- Promo department renamed or empty → banner links to Shop

## 11. Acceptance criteria
- [x] Department tiles with counts and links; Deals tile with highest discount
- [x] Featured, deals (by discount), new arrivals, best sellers with real cards and correct order
- [x] Unlisted (draft, inactive category) products never shown
- [x] Empty sections hidden
- [x] Promo banners link to real departments or Shop
- [x] Sections cached for 10 minutes

## 12. Multi-vendor impact
None. Sections are store-wide; a later "featured by vendor" would be a new query.

## 13. Open questions
- [x] Customer reviews section until F-027? → keep the static samples (user, 2026-10-07)
- [x] Promo banners static or from settings? → static, with real category links (user, 2026-10-07)
