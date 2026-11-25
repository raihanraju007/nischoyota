# F-019: Home page — Tasks

Status: `[ ]` todo · `[~]` doing · `[x]` done. Tick items as they finish. Commit after each green group.

## Backend
- [x] `ProductListingRepository`: featured, biggest deals, newest, department tiles
- [x] `ProductCardData::toArray()/fromArray()`, `HomePageData`
- [x] `HomePageService::sections()` (cached 10 min), `ProductListingService::dealHoursLeft()`
- [x] `HomeController@index`, route in Catalog `routes/web.php`, remove `Route::view('/')`

## Frontend
- [x] `home.blade.php` with real sections, empty sections hidden
- [x] `<x-category-card>` accepts an image URL; `<x-promo-banners>` real links

## Tests
- [x] Feature: sections show the right products in the right order
- [x] Feature: unlisted products hidden, empty sections hidden
- [x] Feature: department tiles, Deals tile, promo links
- [x] Feature: caching

## Verification
- [x] Narrow tests pass (Catalog + storefront pages)
- [x] `vendor/bin/pint --dirty --format agent`
- [x] Dev-site check (curl on seeded data: tiles, sections, promo links, cache entry)
- [ ] Visual browser check (not done)

## Documentation
- [x] `feature.md` status → Done
- [x] Roadmap `docs/tasks.md` updated
- [x] `docs/memory.md` updated
