# F-012: CMS pages — Tasks

## Database
- [x] Migration `pages`; `Page` model; factory; `PageSeeder` (Markdown from the current views)

## Backend
- [x] `PageRepository`; `PageService` (CRUD, system-page rules, `renderBody()`); `SystemPageException`
- [x] `SavePageRequest`; `Admin\PageController`; routes

## Frontend
- [x] Pages list; create/edit form with Markdown help; sidebar link

## Tests
- [x] Rendering safety; CRUD; system-page rules; seeder; authorization

## Verification / Documentation
- [x] Content tests + layout tests; pint; migrate; build; HTTP; docs
