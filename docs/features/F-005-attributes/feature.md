# F-005: Attributes

- **Status:** Done
- **Module:** `app/Modules/Catalog`
- **Phase:** 1
- **Depends on:** F-002, ADR-007 (D5)

## 1. Objective
Admins and staff manage product options (Color, Size, Wattage…) and their values. Product variants (F-007)
are combinations of these values, and the shop filters (F-016) use them.

## 2. User stories
- As **staff/admin**, I want to create an attribute like "Color" and add values like "White" with a swatch colour.
- As **staff/admin**, I want to rename, reorder and delete values.

## 3. Business rules
1. Attribute name required, max 50, unique (case-insensitive); slug generated (unique), editable like categories.
2. Value required, max 50, unique **within its attribute** (case-insensitive).
3. Swatch colour optional, format `#RRGGBB`, stored uppercase. Shown as a swatch in admin (and later on the storefront).
4. Values are ordered by `sort_order`, then value.
5. Deleting an attribute deletes its values (confirmation says so). Once variants exist (F-007), attributes/values in use cannot be deleted.
6. Staff and admins can manage attributes (ADR-012).

## 4. Scope
- Tables `attributes`, `attribute_values`; models, factories, seeder (Color with swatches, Size, Blade Size, Wattage)
- 🎨 `/admin/attributes`: list (name, slug, value chips with swatches, value count) + Add/Edit Attribute form (same layout as Brands)
- 🎨 `/admin/attributes/{attribute}/edit`: attribute form + its values table with an Add/Edit Value form (value, swatch colour, sort order) and delete
- Sidebar link "Attributes" under Catalog

## 5. Out of scope
- Variant generation (F-007), storefront filters (F-016), "in use" delete guards (F-007)

## 6. UI requirements
- Reuse list+form layout (design.md), `.swatch` for colours, `x-confirm-modal method="DELETE"`, empty states, toasts

## 7. Database requirements
| Table | Columns | Notes |
|---|---|---|
| attributes | `name` unique, `slug` unique | |
| attribute_values | `attribute_id` FK cascade, `value`, `color_hex` char(7) nullable, `sort_order` | unique(`attribute_id`, `value`), index(`attribute_id`, `sort_order`) |

## 8. Routes / API
| Method | URI | Name |
|---|---|---|
| GET/POST | /admin/attributes | admin.attributes.index / .store |
| GET | /admin/attributes/{attribute}/edit | admin.attributes.edit (`?value={id}` loads a value into the value form) |
| PUT/DELETE | /admin/attributes/{attribute} | admin.attributes.update / .destroy |
| POST | /admin/attributes/{attribute}/values | admin.attributes.values.store |
| PUT/DELETE | /admin/attributes/{attribute}/values/{value} | admin.attributes.values.update / .destroy (scoped: value must belong to attribute) |

## 9. Security requirements
- Admin middleware; FormRequests; colour validated by regex before being used in an inline `style`

## 10. Edge cases
- Same value in two attributes ("White" in Color and in Frame Color) is allowed
- A value id from another attribute in the URL → 404
- `?value=` with an id from another attribute → ignored

## 11. Acceptance criteria
- [x] CRUD for attributes and values, with validation (uniqueness rules, colour format)
- [x] Values ordered by sort order; swatches shown
- [x] Deleting an attribute removes its values
- [x] Scoped value routes (404 for a foreign value)
- [x] Guests/customers kept out
- [x] Seeder creates Color, Size, Blade Size, Wattage

## 12. Multi-vendor impact
None. Attributes stay global.

## 13. Open questions
- None.
