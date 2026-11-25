# F-007: Product variants

- **Status:** Done
- **Module:** `app/Modules/Catalog`
- **Phase:** 1
- **Depends on:** F-005, F-006, ADR-007 (D5)

## 1. Objective
A product can have options (Color, Size…). Each combination is a variant with its own SKU, price, old price,
cost price, stock and photo. Simple products keep their single default variant.

## 2. User stories
- As **staff/admin**, I want to pick option values (Color: White, Black × Size: 48", 56") and get all combinations as variants.
- As **staff/admin**, I want to set price, stock and photo per variant, and choose the default one.

## 3. Business rules
1. Options are chosen on a separate **Variants page** per product. Up to **3 attributes** and **100 combinations**.
2. Generate keeps existing combinations (their SKU, prices, stock stay), creates missing ones and deletes combinations no longer selected. (F-023: a variant with orders is deactivated instead of deleted.)
3. New variants copy price, old price and cost price from the current default variant, start with stock 0, active, and get a generated SKU.
4. The first time a simple product gets options, its existing default variant becomes the first combination (its SKU and stock are kept).
5. Selecting no values turns the product back into a simple product: the default variant is kept without options; all other variants are deleted.
6. Exactly one default variant, and it must be active. If the default is deleted, the first remaining variant becomes default. At least one variant must stay active.
7. Per variant: SKU (same rules as F-006, unique), price > 0, old price > price, cost price (admin only), stock 0–1,000,000, active, photo (one of the product's photos, optional).
8. Product cache: `min_price` = lowest price of active variants; `max_discount_percent` = highest discount among active variants.
9. When a product has options, the product form's Pricing & Stock panel shows a summary + "Manage variants" instead of the price/stock fields.
10. Product list: price "from ৳X" for products with options; stock = total of active variants; "Out of stock" = no active variant with stock.
11. An attribute value used by a variant cannot be deleted; an attribute whose values are used cannot be deleted (F-005 guard).

## 4. Scope
- Pivot `attribute_value_product_variant`; `product_variants.product_image_id` (photo) and drop `product_images.product_variant_id` (one photo can serve many variants)
- Variants page 🎨 `/admin/products/{product}/variants`: options panel (checkbox values per attribute) + variants table form
- Product form / list changes (rules 9, 10); attribute delete guards (rule 11)
- Seeder: give 3 demo products options (Ceiling Fan: Color × Blade Size, T-Shirt: Color × Size, LED Bulb: Wattage)

## 5. Out of scope
- Storefront variant picker (F-017), stock audit log (F-008)

## 7. Database requirements
| Table | Columns | Notes |
|---|---|---|
| attribute_value_product_variant | `product_variant_id` FK cascade, `attribute_value_id` FK **restrict** | composite PK; restrict so a used value cannot vanish |
| product_variants | + `product_image_id` nullable FK → product_images, null on delete | variant photo |
| product_images | − `product_variant_id` | replaced by the column above |

## 8. Routes / API
| Method | URI | Name |
|---|---|---|
| GET | /admin/products/{product}/variants | admin.products.variants.edit |
| POST | /admin/products/{product}/variants/generate | admin.products.variants.generate |
| PUT | /admin/products/{product}/variants | admin.products.variants.update |

## 9. Security requirements
- Value ids must belong to the attribute they are posted under; variant ids and photo ids must belong to the product; cost price ignored for staff

## 10. Edge cases
- Duplicate SKUs within one save → error on the row
- Making an inactive variant the default → error
- Deactivating every variant → error
- 4 attributes or 101 combinations → error

## 11. Acceptance criteria
- [x] Generate creates the cartesian combinations, keeps existing ones, deletes removed ones
- [x] Simple → options keeps the original variant; options → simple keeps the default
- [x] Table save updates SKU/prices/stock/photo/active/default with validation
- [x] Cached min price / discount from active variants
- [x] Product form and list behave per rules 9 and 10
- [x] Attribute/value delete guards
- [x] Staff cannot see or change cost price on the variants page

## 12. Multi-vendor impact
None; variants stay under their product.

## 13. Open questions
- [x] Separate Variants page — approved 2026-10-06
- [x] Photo per variant — approved 2026-10-06
- [x] Delete removed combinations now, deactivate once ordered — approved 2026-10-06
