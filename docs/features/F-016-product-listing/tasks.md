# F-016: Product listing — Tasks

## Backend
- [x] `ProductSort` enum; `ProductListingFilter`, `ProductCardData`, `FilterOptions` DTOs
- [x] `ProductListingRepository` (scoped query, filters, sort, facets); `ProductListingService` (cards, chips)
- [x] `ListProductsForShopRequest`; `ShopController`; routes

## Frontend
- [x] Listing, filters (GET form, swatch/size checkboxes, radios), sort form, chips, pagination, product card data mode
- [x] Shop, category (banner, chips), deals (tag, countdown, chips) views; JS auto-submit

## Tests
- [x] Scope, each filter, sort, pagination, cards, 404, chips

## Verification / Documentation
- [x] Catalog + storefront tests; pint; build; HTTP; docs
