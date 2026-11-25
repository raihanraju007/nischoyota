# F-018: Search

- **Status:** Done
- **Module:** `app/Modules/Catalog`
- **Phase:** 1
- **Depends on:** F-016

## 1. Objective
Customers find products by typing in the header (or the mobile Search tab) and narrow the results with the same filters and sorting as the shop.

## 2. User stories
- As a **customer**, I want to type "walton fan" and see matching products.
- As a **customer**, I want to filter and sort search results, and get suggestions when nothing matches.

## 3. Business rules
1. Query `q`: trimmed, 2–100 characters, split into words (max 5). **Every word** must match the product name, a variant SKU, the brand name or the category name (case-insensitive, partial words allowed). `%` and `_` are plain text.
2. Only listed products (active, active category), as in F-016.
3. Default sort **Best match**: exact name → name starts with the query → name contains the query → other matches; then popularity. All F-016 sorts are also available.
4. Filters (category, price, brand, rating, availability, discount, colour, size) and their options are **scoped to the search results**.
5. `/search` without a query (mobile Search tab) shows a search box and the departments. A query shorter than 2 characters shows "Type at least 2 characters".
6. No results: the prototype "No products found" page with **real** best-selling products under "You may like these".
7. The header search box keeps the query.
8. MySQL FULLTEXT is **not** used yet: InnoDB FULLTEXT does not see uncommitted rows (tests run in transactions) and the catalogue is small, so word-by-word `LIKE` is used. Revisit in F-030 (performance).

## 4. Scope
- `ListingScope` DTO (categories, discounted only, search term) replacing the two scope parameters in `ProductListingRepository`
- `ProductSort::BestMatch`; search conditions and relevance ordering; `SearchController`; route replaces the static ones
- Views: `shop/search` (results / prompt), `shop/search-empty` (real suggestions)

## 5. Out of scope
- Live suggestions while typing, search history/analytics, Bangla transliteration, typo correction

## 11. Acceptance criteria
- [x] Matches by name, SKU, brand, category; all words required; wildcards escaped
- [x] Best match order; other sorts; filters scoped to results
- [x] Empty query page, too-short query, no-results page with real suggestions
- [x] Only listed products

## 12. Multi-vendor impact
None.

## 13. Open questions
- None.
