# F-007: Product variants — Tasks

## Database
- [x] Migrations: pivot; variant photo column; drop product_images.product_variant_id
- [x] Relations: `ProductVariant::attributeValues()`, `image()`, `label()`; factory state `withOptions`

## Backend
- [x] `ProductVariantService` (generate combinations, save table, refresh cached prices) + repository methods
- [x] Requests `GenerateVariantsRequest`, `UpdateVariantsRequest`; `ProductVariantController`; routes
- [x] `ProductService`/`SaveProductRequest`: no price/stock fields when the product has options
- [x] List: price "from", total stock, out-of-stock rule
- [x] Attribute / value delete guards

## Frontend
- [x] Variants page; product form summary + "Manage variants"; list changes

## Tests
- [x] Feature: generate, simple↔options, table save + validation, cached prices, form/list, guards, staff cost price, authorization

## Verification / Documentation
- [x] Tests, pint, migrate:fresh --seed, build, HTTP check; docs
