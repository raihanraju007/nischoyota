# F-017: Product detail page

- **Status:** Done
- **Module:** `app/Modules/Catalog` (uses `Location` and `Content` services for delivery info)
- **Phase:** 1
- **Depends on:** F-007, F-009, F-010, F-016

## 1. Objective
`/product/{slug}` shows a real product: photos, options, price and stock per variant, description, specifications,
delivery information and related products — ready for Add to Cart in F-021.

## 2. User stories
- As a **customer**, I want to choose a colour/size and see that variant's price, stock and photo.
- As a **customer**, I want to read the description and specifications and know the delivery charge and time.

## 3. Business rules
1. Only an **active** product in an **active** category is shown; otherwise 404. Title/description use the SEO fields, falling back to name / short description.
2. Breadcrumbs: department → subcategory → product. Badge NEW / BEST SELLER as on cards (F-016).
3. **Gallery:** product photos in order (cover first); thumbnails switch the main photo.
4. **Options:** one group per attribute used by active variants (values in attribute sort order). The default variant is pre-selected. Choosing values selects the matching active variant and updates price, old price, "-X%", stock note, SKU and photo (variant photo if set). A combination that does not exist shows "This combination is not available" and disables buying. Without JS a variant dropdown is used.
5. **Stock note:** in stock → "In Stock — ships within 24 hours"; ≤ low-stock threshold (F-010) → "Only N left"; 0 with pre-order → "Available for pre-order"; 0 without → "Out of stock" (buying disabled).
6. **Description** is Markdown, rendered safely (HTML stripped, like CMS pages). Specifications tab only when there are specifications.
7. **Delivery box:** COD everywhere, delivery time = fastest–slowest active zone ("1–5 days"), charges per active zone and free-delivery threshold (Settings), 7-day returns link. The prototype's free-text "check delivery" box is dropped (checkout uses the area dropdowns).
8. **Reviews tab:** real average/count; "No reviews yet" until reviews exist (F-027). Sample reviews and the rating bars are removed until then.
9. **Related:** up to 4 listed products from the same department (all its subcategories), the product's own subcategory first; excluding this product.
10. Add to Cart / Buy Now / wishlist stay visual until F-021 / F-022; the selected `variant_id` is already in the buy form.
11. Quick View stays off on real cards (decided in F-016): cards open this page.

## 4. Scope
- `ProductPageService` (load, variant picker data, related cards, delivery summary), `ProductPageController@show`, route `/product/{slug}`
- `products/show` view rewritten on real data; product description hint in the admin form
- JS: variant picker (data from a JSON script tag)

## 5. Out of scope
- Cart (F-021), wishlist (F-022), reviews (F-027), recently viewed, Q&A

## 11. Acceptance criteria
- [x] 404 for draft/archived/hidden-category products
- [x] Gallery, breadcrumbs, badges, brand link, SKU
- [x] Option groups and picker data match active variants; default pre-selected; no-JS select
- [x] Stock notes for each case
- [x] Safe Markdown description; specs tab only with specs
- [x] Delivery summary from zones/settings; related products
- [x] No N+1 queries

## 12. Multi-vendor impact
Phase 2 adds "Sold by" from the product's vendor; the page data comes from one service.

## 13. Open questions
- None.
