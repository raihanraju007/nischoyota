# F-004: Brands

- **Status:** Done
- **Module:** `app/Modules/Catalog`
- **Phase:** 1
- **Depends on:** F-002, F-003, ADR-010

## 1. Objective
Admins and staff manage the brands that products belong to and that the shop "Brand" filter shows.

## 2. User stories
- As **staff/admin**, I want to add, edit, hide and delete brands with a logo.

## 3. Business rules
1. Name required (max 100), unique (case-insensitive).
2. Slug: same rules as categories (F-003 rule 3).
3. Logo optional: PNG, JPG or WebP, max 1 MB, public disk under `brands/`; no SVG. Replacing/removing/deleting removes the old file.
4. Inactive brands stay in the admin list but are hidden from the storefront filter (F-016).
5. A brand used by products cannot be deleted — the check is added in F-006 when products exist.
6. List order: name A–Z. Staff and admins can manage brands (ADR-012).

## 4. Scope
- `brands` table, `Brand` model/factory/seeder (Walton, Vision, Klick, Superstar, Deshi Basics — no logos)
- New admin page 🎨 `/admin/brands`: same layout as Categories (table + add/edit form panel), search by name, pagination (20/page)
- Sidebar link "Brands" under Catalog
- Shared `App\Support\PublicImageStorage` for upload/replace/remove/delete, used by categories and brands (removes duplicated image logic)

## 5. Out of scope
- Products count column and "in use" delete guard (F-006); storefront filter (F-016)

## 6. UI requirements
- Reuse: `x-layouts.admin`, `.admin-grid`, `.admin-table`, `.table-product`, `x-form.*`, `.upload-box`, `.switch`, `x-confirm-modal method="DELETE"`, `x-empty-state`, `.admin-toolbar`/`.admin-search`
- Empty state; "No brands match" when a search finds nothing; toasts after save/delete

## 7. Database requirements
| Table | Columns | Notes |
|---|---|---|
| brands | `name` unique, `slug` unique, `logo` nullable, `is_active` bool index | |

## 8. Routes / API
| Method | URI | Name | Auth |
|---|---|---|---|
| GET | /admin/brands | admin.brands.index | admin, staff |
| POST | /admin/brands | admin.brands.store | admin, staff |
| GET | /admin/brands/{brand}/edit | admin.brands.edit | admin, staff |
| PUT | /admin/brands/{brand} | admin.brands.update | admin, staff |
| DELETE | /admin/brands/{brand} | admin.brands.destroy | admin, staff |

## 9. Security requirements
- Admin middleware; FormRequest validation; upload type by content, no SVG; search term escaped for `LIKE`

## 10. Edge cases
- "walton" when "Walton" exists → name taken
- Search keeps working with pagination (query string kept)

## 11. Acceptance criteria
- [x] Create, edit, delete a brand, with and without logo
- [x] Name and slug uniqueness, slug generation
- [x] Old logo file removed on replace / remove / delete
- [x] Search and pagination
- [x] Guests/customers kept out
- [x] Category image behaviour unchanged after moving to `PublicImageStorage`

## 12. Multi-vendor impact
None. Brands stay global.

## 13. Open questions
- None.
