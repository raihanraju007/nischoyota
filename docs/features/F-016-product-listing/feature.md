# F-016: Product listing, filters and sorting

- **Status:** Done
- **Module:** `app/Modules/Catalog` (storefront side)
- **Phase:** 1
- **Depends on:** F-007, F-008, F-013

## 1. Objective
Shop, Category and Deals pages show the store's real active products, with working filters, sorting and pagination.

## 2. User stories
- As a **customer**, I want to browse all products or one category, narrow them down (brand, price, colour, size, rating, discount, stock) and sort them.
- As a **customer**, I want a Deals page with everything on discount.

## 3. Business rules
1. Only **active** products in an **active** category are listed.
2. **Pages:** `/shop` (all); `/category/{slug}` (a department includes its subcategories; subcategory chips; banner with the category image and SEO description; unknown or hidden category → 404; `/category` without a slug → `/shop`); `/deals` (products with a discount; department chips filter by `?category=`; "Up to X% OFF" = highest discount; countdown to midnight, Bangladesh time).
3. **Filters** (GET query string, shareable URLs): `category[]` (slug, Shop only), `brand[]` (slug), `price_min` / `price_max` (taka, on the lowest price), `rating` (4/3/2 ★ & up — one choice), `discount` (10/20/40 % or more — one choice), `availability[]` (`in-stock`: an active variant has stock; `pre-order`: pre-order allowed), `color[]` / `size[]` (values of the attributes with slug `color` / `size`; an active variant must have the value).
4. Filter options come from the data: categories with product counts, brands that have listed products, colours/sizes used by active variants.
5. **Sort:** Popular (sold count, default), Newest (published), Price low→high, Price high→low, Highest rated. Ties broken by id.
6. **24 products per page**; filters and sort stay in the page links. Active filters appear as removable chips with "Clear all".
7. **Card:** cover photo, category, name, rating (count), lowest price, old price and "-X%" when discounted, badge **NEW** (published in the last 14 days) or **BEST SELLER** (≥ 50 sold), "Out of Stock" when no active variant has stock and pre-order is off. Links go to `/product/{slug}` (the product page becomes dynamic in F-017).
8. Filters submit automatically on change (JS) and with an "Apply filters" button (no JS). Rating and discount become single-choice (radio) instead of the prototype's checkboxes, because "4★ & up" and "3★ & up" overlap.
9. Empty result → "No products match these filters" with "Clear all filters".

## 4. Scope
- `ProductListingRepository`, `ProductListingService`, `ProductListingFilter`, `ProductSort`, `ProductCardData`, `FilterOptions`
- `ListProductsForShopRequest`, `ShopController` (shop, category, deals); routes replace the static ones (`/product/{slug?}` gets an optional slug)
- `x-shop.listing`, `x-shop.filters`, `x-shop.sort-select`, `x-product-card` (accepts `ProductCardData`), shop/category/deals views; small JS (auto-submit)
- Quick View is not shown on real cards (the static modal stays on prototype pages until F-017)

## 5. Out of scope
- Product page (F-017), search (F-018), home page (F-019), add to cart / wishlist (F-021/F-022)

## 11. Acceptance criteria
- [x] Shop / category / deals list only active products in active categories; 404 for hidden category
- [x] Each filter, combined filters, filter options, chips
- [x] Each sort; pagination keeps the query
- [x] Card data: price, discount, badges, out of stock
- [x] No N+1 queries (strict mode on in tests)

## 12. Multi-vendor impact
A vendor filter would be one more `ProductListingFilter` field; listing queries stay in one repository.

## 13. Open questions
- None.
