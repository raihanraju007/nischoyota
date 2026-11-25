# F-012: CMS pages

- **Status:** Done
- **Module:** `app/Modules/Content`
- **Phase:** 1
- **Depends on:** F-002, F-010, ADR-012

## 1. Objective
The admin edits the text of About, FAQ, Privacy, Terms, Shipping and Returns without a developer. (The storefront
starts reading these pages in F-020.)

## 2. User stories
- As the **owner**, I want to update a policy (e.g. the return window) and publish it.
- As the **owner**, I want to add, edit and reorder FAQ questions.

## 3. Business rules
1. Page: title (≤ 120), slug (unique, lowercase/dashes), subtitle (hero line, ≤ 255, optional), body in **Markdown** (≤ 50,000), SEO title/description, published.
2. Body is Markdown, rendered with Laravel's CommonMark (`Str::markdown`) with raw HTML **stripped** and unsafe links (`javascript:`) removed — no HTML injection.
3. FAQ page: each `## Heading` is a question; the text under it is the answer (the storefront shows them as the accordion).
4. The six **system pages** (about, faq, privacy-policy, terms, shipping-policy, return-policy) cannot be deleted and their slug cannot change (storefront routes use them). Extra pages can be created and deleted.
5. "Last updated" on the storefront = the page's `updated_at`.
6. Pages are **admin only** (`manage-store`, ADR-012).

## 4. Scope
- `pages` table (+ `is_system`), `Page` model, factory, `PageSeeder` (current storefront text converted to Markdown)
- `PageRepository`, `PageService` (CRUD, `renderBody()`), requests, `Admin\PageController`
- 🎨 `/admin/pages` list (title, URL, status, updated) and create/edit page with a Markdown help box; sidebar "Pages" (admin only)

## 5. Out of scope
- Storefront rendering from the database and the contact form (F-020), image uploads inside pages, revisions

## 7. Database requirements
`pages` as in database.md §3.7, plus `subtitle` (hero line) and `is_system`.

## 8. Routes / API
`Route::resource('pages')->except('show')` under `/admin`, `can:manage-store`.

## 11. Acceptance criteria
- [x] Seeder creates the six pages with today's storefront text
- [x] CRUD with validation; system pages keep their slug and cannot be deleted
- [x] Markdown rendering strips HTML and unsafe links (unit test)
- [x] Admin only; staff 403

## 12. Multi-vendor impact
None.

## 13. Open questions
- None (Markdown chosen: safe without adding an HTML-sanitizer package).
