# F-003: Categories

- **Status:** Done
- **Module:** `app/Modules/Catalog`
- **Phase:** 1
- **Depends on:** F-002, ADR-007, ADR-010

## 1. Objective
Admins and staff manage the category tree (department → subcategory) that the storefront menu,
shop filters and product form will use.

## 2. User stories
- As **staff/admin**, I want to add, edit and delete categories with an image, so customers can browse by department.
- As **staff/admin**, I want to put subcategories under a department and order them, so the menu reads well.
- As **staff/admin**, I want to hide a category without deleting it.

## 3. Business rules
1. Two levels only: a category is top level (`parent_id` null) or the child of a **top-level** category.
2. A category cannot be its own parent; a category that has children cannot become a child.
3. Slug: lowercase, unique, `a-z 0-9 -`. Empty slug → generated from the name; a taken generated slug gets `-2`, `-3`…; a taken slug typed by the user is a validation error.
4. A category with subcategories cannot be deleted ("Delete or move its subcategories first."). F-006 adds the same check for products.
5. Image is optional: PNG, JPG or WebP, max 1 MB, stored on the public disk under `categories/` (ADR-010). Replacing or deleting removes the old file. **SVG uploads are not allowed** (an uploaded SVG can run scripts on our domain).
6. Order: `sort_order` ascending, then name. Default sort order 0.
7. Inactive categories stay in the admin list but are hidden from the storefront (used from F-013).
8. Staff and admins can both manage categories (ADR-012).

## 4. Scope
- `Catalog` module (provider, model, repository, service, requests, controller, routes)
- Admin categories page: tree list (children indented under their parent), add form, edit (same page, form filled in), delete with confirmation
- Fields: name, slug, parent, image, sort order, active, SEO meta title/description
- Seeder: the 5 departments and their subcategories from the current storefront menu, with icons

## 5. Out of scope
- Storefront menu / category pages using this data (F-013, F-016)
- "Products" count column — it returns in F-006 once products exist
- Drag-and-drop ordering, more than two levels, bulk actions

## 6. UI requirements
- Page: `resources/views/admin/categories/index.blade.php` (existing design). The right panel is "Add Category", or "Edit Category" with a Cancel link when editing.
- New fields in the same style: Sort order (number), SEO title, SEO description (`x-form.input` / `x-form.textarea`).
- Table: image (placeholder when none), name, `/slug`, parent shown by indentation (`— Fans`), status pill (Active/Hidden), edit + delete.
- Delete uses the existing confirm modal, now submitting a real `DELETE` form.
- Empty state when there are no categories. Success toasts after save/delete.

## 7. Database requirements
| Table | Columns (key ones) | Notes |
|---|---|---|
| categories | `parent_id` nullable self FK (restrict delete), `name`, `slug` unique, `image` nullable, `sort_order` unsigned small int, `is_active` bool, `meta_title`, `meta_description` nullable | index (`parent_id`, `sort_order`) |

## 8. Routes / API
| Method | URI | Name | Controller@action | Auth |
|---|---|---|---|---|
| GET | /admin/categories | admin.categories.index | Admin\CategoryController@index | admin, staff |
| POST | /admin/categories | admin.categories.store | @store | admin, staff |
| GET | /admin/categories/{category}/edit | admin.categories.edit | @edit | admin, staff |
| PUT | /admin/categories/{category} | admin.categories.update | @update | admin, staff |
| DELETE | /admin/categories/{category} | admin.categories.destroy | @destroy | admin, staff |

## 9. Security requirements
- Admin middleware on all routes; FormRequests validate everything; `parent_id` must exist and be top level
- Upload type checked by content (`mimes` + `image`), no SVG, max size 1 MB

## 10. Edge cases
- Editing a parent that has children and choosing a parent → rejected (rule 2)
- Choosing itself as parent → rejected
- Two categories named "Fans" → second gets `fans-2`
- Removing the image ("Remove image" checkbox) deletes the file
- Deleting a child category is allowed

## 11. Acceptance criteria
- [x] Create, edit and delete a category, with and without image
- [x] Two-level rule and self-parent rule enforced
- [x] Slug generated, de-duplicated, validated
- [x] Category with children cannot be deleted
- [x] Old image file removed on replace / remove / delete
- [x] Customers and guests cannot reach any category admin route
- [x] List shows children under their parent in sort order
- [x] Seeder creates the 5 departments with subcategories

## 12. Multi-vendor impact
None. Categories stay global in Phase 2; vendors pick from them.

## 13. Open questions
- [x] No SVG uploads; PNG/JPG/WebP only — approved 2026-10-06
- [x] "Products" column hidden until F-006 — approved 2026-10-06
