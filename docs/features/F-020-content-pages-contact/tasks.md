# F-020: Content pages & contact form — Tasks

Status: `[ ]` todo · `[~]` doing · `[x]` done. Tick items as they finish. Commit after each green group.

## Database
- [x] `contact_messages` migration
- [x] `ContactMessage` model, enums `ContactSubject`, `ContactMessageStatus`
- [x] Factory (+ replied / closed states)

## Backend
- [x] `PageRepository::findPublishedBySlug()`, `PageService::findPublished()`, `faqItems()`, `PageTemplate`
- [x] `ContactMessageRepository`, `ContactMessageService`, `ContactMessageData`
- [x] `SendContactMessageRequest` (phone normalised, honeypot)
- [x] Storefront `PageController`, `ContactController`; module `routes/web.php`; remove `Route::view()` lines

## Frontend
- [x] `pages/show`, `pages/about`, `pages/faq` from data; delete static policy views
- [x] `pages/contact`: channels from settings, real form, errors, prefill

## Tests
- [x] Feature: pages render, drafts 404, `/page/{slug}`, FAQ, last updated
- [x] Feature: contact saves, validation, prefill + user link, honeypot, rate limit

## Verification
- [x] Narrow tests pass
- [x] `vendor/bin/pint --dirty --format agent`
- [x] Dev-site check (curl: 6 system pages 200, FAQ 8 items, last-updated date, /page/x 404, contact 200)

## Documentation
- [x] `feature.md` status → Done
- [x] Roadmap `docs/tasks.md` updated (M2)
- [x] `docs/memory.md`, `docs/architecture.md`
