# F-018: Search — Tasks

## Backend
- [x] `ListingScope`; refactor `ProductListingRepository`/`ProductListingService` + callers
- [x] Search conditions + `ProductSort::BestMatch`; `SearchRequest`; `SearchController`; route

## Frontend
- [x] `shop/search` (results / prompt / too short), `shop/search-empty` (real suggestions)

## Tests
- [x] Matching, words, escaping, relevance, scoped filters, empty/short/no-results; F-016/F-017 still green

## Verification / Documentation
- [x] Catalog + storefront tests; pint; build; HTTP; docs
