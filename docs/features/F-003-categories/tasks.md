# F-003: Categories — Tasks

Status: `[ ]` todo · `[~]` doing · `[x]` done. Tick items as they finish.

## Database
- [x] Migration `categories`
- [x] `Category` model (parent/children relations, casts, `imageUrl()`), factory (states: child, inactive, withImage), seeder

## Backend
- [x] `CatalogServiceProvider` + `bootstrap/providers.php`
- [x] `CategoryRepository` (tree for admin, top-level options, slug exists, create/update/delete, has children)
- [x] `CategoryService` (slug generation, image store/replace/remove, two-level rule, delete guard) + `CategoryHasChildrenException`
- [x] `CategoryData` DTO; `StoreCategoryRequest`, `UpdateCategoryRequest`
- [x] `Admin\CategoryController` (index, store, edit, update, destroy) + `routes/admin.php`
- [x] Remove the static `admin.categories.index` route

## Frontend
- [x] Categories page: real list, add/edit form, delete form in confirm modal
- [x] `x-confirm-modal`: optional real form (`action`, `method`) + JS sets the action per row
- [x] `x-form.select`: value/label options (needed for `parent_id`)

## Tests
- [x] Feature: list, create, edit, delete, validation, two-level rules, slug, image upload/replace/remove, authorization
- [x] Unit: slug generation

## Verification
- [x] Tests, pint, migrate:fresh --seed, build, `storage:link`

## Documentation
- [x] feature status, roadmap, memory, architecture (Catalog → Built), database.md
