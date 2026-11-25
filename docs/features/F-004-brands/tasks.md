# F-004: Brands — Tasks

## Database
- [x] Migration `brands`; `Brand` model, factory (inactive, withLogo), `BrandSeeder`

## Backend
- [x] `App\Support\PublicImageStorage`; refactor `CategoryService` to use it
- [x] `BrandRepository`, `BrandService`, `BrandData`, `SaveBrandRequest`, `Admin\BrandController`, routes

## Frontend
- [x] `admin/brands/index` (table, search, pagination, form, delete modal); sidebar link

## Tests
- [x] Feature: CRUD, validation, logo files, search, pagination, authorization; categories suite still green

## Verification
- [x] Tests, pint, migrate:fresh --seed, build, HTTP check

## Documentation
- [x] feature status, roadmap, memory, architecture, database.md, design.md (Brands page)
