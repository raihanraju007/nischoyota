# F-006: Products (core) — Tasks

## Database
- [x] Migrations `products`, `product_variants`, `product_images`
- [x] `ProductStatus` enum; `Product`, `ProductVariant`, `ProductImage` models; factories; `ProductSeeder`

## Backend
- [x] `ProductRepository` (admin list + counts, find for edit, SKU/slug exists, create/update/delete, images, bulk status)
- [x] `ProductService` (save with default variant, SKU, cached prices, published_at, images, delete, bulk)
- [x] DTOs, `SaveProductRequest`, `ListProductsRequest`, `BulkProductActionRequest`, `ProductController`, routes
- [x] Category/brand product counts + delete guards

## Frontend
- [x] Product list (tabs, search, filter, pagination, bulk bar)
- [x] Product form (create/edit, prices, cost price admin-only, images manager, specs repeater, SEO)
- [x] JS: repeater rows, select-all

## Tests
- [x] Feature: create/edit/delete, validation, staff cost price, SKU, images, specs, cached prices, list filters, bulk, guards, authorization

## Verification
- [x] Tests, pint, migrate:fresh --seed, build, HTTP check

## Documentation
- [x] feature status, roadmap, memory, architecture, database.md
