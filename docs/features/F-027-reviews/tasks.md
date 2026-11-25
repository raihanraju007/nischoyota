# F-027: Reviews — Tasks

Status: `[ ]` todo · `[~]` doing · `[x]` done.

## Database
- [x] `reviews` migration, `Review` model, factory with states

## Backend
- [x] `Review` module provider + registration
- [x] `ReviewStatus`, `ReviewData`, `ReviewSummary`, `ReviewRepository`, `ReviewService`
- [x] `ProductService::updateRatingCache()`, `CustomerOrderService::hasDeliveredProduct()`
- [x] Storefront `ReviewController@store`, admin `ReviewController`, requests, routes; static admin route removed
- [x] Composers: product page reviews, home reviews, admin pending badge

## Frontend
- [x] Product page Reviews tab (summary, bars, list, form / own status)
- [x] `admin/reviews/index` (tabs, table, approve/reject, pagination)
- [x] Home testimonials from approved reviews

## Tests
- [x] Write/edit review, validation, guest/staff refused, verified purchase
- [x] Approve/reject, rating cache, tabs, badge
- [x] Product page and home page show approved reviews only

## Verification
- [x] Narrow tests pass
- [x] `vendor/bin/pint --dirty --format agent`

## Documentation
- [x] Status, roadmap, memory, architecture, index
