# F-013: Storefront shell — Tasks

## Backend
- [x] `CategoryRepository::activeMenuTree()`, `CategoryService::menuTree()` (cached) + forget on create/update/delete
- [x] `StorefrontShellComposer`; register in `AppServiceProvider`
- [x] Route `/category/{slug?}`

## Frontend
- [x] Header (top bar, nav, mega menu, account, badges), mobile menu, footer

## Tests
- [x] Feature: menu from DB (inactive hidden, order), settings in top bar/footer, guest vs signed-in, badges, cache refresh; StorefrontPagesTest still green

## Verification / Documentation
- [x] Related tests; pint; build; HTTP; docs
