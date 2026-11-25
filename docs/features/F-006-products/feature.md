# F-006: Products (core)

- **Status:** Done
- **Module:** `app/Modules/Catalog`
- **Phase:** 1
- **Depends on:** F-003, F-004, ADR-007 (D4, D5), ADR-010, ADR-012

## 1. Objective
Admins and staff create and manage products with price, stock, images and specifications, so the
storefront (F-016, F-017) has real products. Every product gets one **default variant** that holds
SKU, price and stock (D5); F-007 adds more variants.

## 2. User stories
- As **staff/admin**, I want to add a product with photos, price, stock and specifications.
- As **staff/admin**, I want to find products by name/SKU, status and category, and publish or archive many at once.
- As the **owner**, I want cost prices visible only to admins.

## 3. Business rules
1. Name required (max 255). Slug from the name, unique; editable in the SEO panel.
2. Category required (any category). Brand optional.
3. Status (`ProductStatus`): draft, active, archived. `published_at` is set the first time a product becomes active and never cleared.
4. Prices are typed in taka (up to 2 decimals) and stored in paisa (D4). Selling price required, > 0. Old price optional and must be higher than the selling price. Cost price optional, ≥ 0.
5. **Cost price is admin-only** (ADR-012): staff don't see the field and a staff save never changes it.
6. SKU optional: uppercase letters, digits and dashes, max 40, unique. Empty → generated `NS-{first 2 letters of the department}-{4 digits}` (e.g. `NS-EL-0035`), always unique.
7. Stock: whole number 0–1,000,000. (From F-008, stock changes move to Inventory with an audit log.)
8. Weight in grams optional.
9. Images: up to 8 per product, JPG/PNG/WebP, max 2 MB each, stored under `products/`. The first image (lowest sort order) is the cover. On edit: remove images, choose the cover, add more (total still ≤ 8).
10. Specifications: up to 30 label/value rows (label ≤ 60, value ≤ 255); fully empty rows are ignored; half-filled rows are a validation error.
11. Cached columns, recalculated on every save: `min_price` = default variant price; `max_discount_percent` = floor((old − price) / old × 100), 0 without an old price.
12. Delete removes the product, its variants and image files. (F-023: a product that has orders is archived instead.)
13. Bulk actions on selected products (max 100): Publish, Move to draft, Archive.
14. List: tabs All / Active / Draft / Archived / Out of stock (with counts); search by name or SKU; filter by category (a department includes its subcategories); 20 per page; newest first. Stock ≤ 5 is shown as low.
15. Categories page shows a Products count again; a category with products cannot be deleted. Brands page shows a Products count; a brand with products cannot be deleted.

## 4. Scope
- Tables `products`, `product_variants`, `product_images`; models, enum, factories, `ProductSeeder` (~30 demo products with photos from `public/images/products`)
- Admin product list (`admin/products/index`) and form (`admin/products/form`, create + edit) made dynamic
- New form panels: Specifications (add/remove rows), SEO (slug, meta title/description); image manager on edit
- Bulk actions; category/brand counts and delete guards

## 5. Out of scope
- Multiple variants / options (F-007), stock audit log (F-008), storefront pages (F-016+), search index (F-018)

## 6. UI requirements
- Keep the prototype layout of both pages. Prices shown with `<x-money>`. Toasts after save/delete/bulk.
- Small generic JS: repeater rows (`data-repeater`), "select all" checkbox (`data-check-all`).

## 7. Database requirements
See [database.md §3.3](../../database.md#33-catalog): `products`, `product_variants`, `product_images` as designed
(variant `attribute_value` pivot comes in F-007). Indexes: `products (status, category_id)`, `(status, min_price)`, `(status, published_at)`.

## 8. Routes / API
| Method | URI | Name |
|---|---|---|
| GET | /admin/products | admin.products.index |
| GET/POST | /admin/products/create, /admin/products | admin.products.create / .store |
| GET/PUT/DELETE | /admin/products/{product}/edit, /admin/products/{product} | .edit / .update / .destroy |
| POST | /admin/products/bulk | admin.products.bulk |

## 9. Security requirements
- Admin middleware; all input via FormRequests; prices recalculated server-side into paisa
- Cost price ignored for staff even if posted; image type checked by content; no SVG

## 10. Edge cases
- Price "4850.5" → 485050 paisa; old price equal to price → error
- Removing the cover image → next image becomes cover
- Uploading 3 images to a product that already has 7 → error ("max 8")
- Bulk action with ids that don't exist → ignored

## 11. Acceptance criteria
- [x] Create / edit / delete a product with default variant, images and specs
- [x] Price, SKU, image and spec validation as above
- [x] Cost price hidden from and ignored for staff
- [x] SKU generated when empty, unique
- [x] `min_price`, `max_discount_percent`, `published_at` correct
- [x] List tabs, counts, search, category filter (incl. subcategories), pagination
- [x] Bulk publish / draft / archive
- [x] Category and brand product counts and delete guards
- [x] Seeder creates demo products with images

## 12. Multi-vendor impact
Phase 2 adds `products.vendor_id`. Product ownership is not assumed anywhere; queries go through the repository.

## 13. Open questions
- [x] SKU auto-generated when empty — approved 2026-10-06
- [x] Specifications editor now — approved 2026-10-06
- [x] Delete now; archive once ordered (F-023) — approved 2026-10-06
- [x] Bulk actions in F-006 — approved 2026-10-06
