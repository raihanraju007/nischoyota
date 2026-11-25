# F-020: Content pages & contact form

- **Status:** Done
- **Module:** `app/Modules/Content`
- **Phase:** 1
- **Depends on:** F-010, F-012

## 1. Objective
The storefront shows the pages the admin edits (F-012) instead of hard-coded text, and the contact form saves real messages for the admin inbox (F-026).

## 2. User stories
- As a **customer**, I want to read up-to-date policies, FAQ and About pages.
- As a **customer**, I want to send the store a message and know it arrived.
- As an **admin**, I want extra pages I create to be reachable on the storefront.

## 3. Business rules
1. System pages keep their URLs: `/about`, `/faq`, `/privacy-policy`, `/terms`, `/shipping-policy`, `/return-policy` (slug = path). Extra pages are at `/page/{slug}`; a system page is not served under `/page/…`.
2. Only **published** pages are shown; anything else is 404.
3. Templates: `faq` → accordion (each `## Heading` is a question, the text under it the answer; text before the first heading is an intro); `about` → prose + trust strip; every other page → policy layout with "Last updated: {updated_at, d F Y}".
4. Body rendered with `SafeMarkdown` (F-012). Page title = `meta_title` or title; description = `meta_description` or the default.
5. Contact channels on `/contact` come from store settings (phone, email, address, hours); empty address/hours are not shown.
6. Contact form: name (required, ≤ 100), phone (required, Bangladeshi mobile, normalised to `01XXXXXXXXX`), email (optional), subject (`ContactSubject`: Order Issue, Product Question, Return Request, Other), message (required, 10–2,000 characters).
7. A signed-in customer's name, phone and email are pre-filled, and the message stores their `user_id`.
8. Saved with status `new` (`ContactMessageStatus`: new, replied, closed). No email notification (admin reads the inbox in F-026).
9. **Honeypot:** a hidden `website` field. If filled, the customer sees the normal success message but nothing is saved.
10. **Rate limit:** 5 messages per 10 minutes per IP (`throttle:5,10`), then 429.
11. Success: back to `/contact` with the toast "Message sent! We'll get back to you within 24 hours."

## 4. Scope
- `contact_messages` migration, `ContactMessage` model + factory, enums `ContactSubject`, `ContactMessageStatus`
- `ContactMessageRepository`, `ContactMessageService`, `SendContactMessageRequest`, `ContactMessageData`
- Storefront `PageController@show`, `ContactController@show/store`; `PageService::findPublished()`, `faqItems()`; `PageTemplate` enum
- Views: `pages/show` (policy), `pages/about`, `pages/faq`, `pages/contact` from data; remove the static policy views and `Route::view()` lines

## 5. Out of scope
- Admin messages inbox (F-026), email notification, CAPTCHA, page preview for drafts

## 7. Database requirements
| Table | Columns (key ones) | Notes |
|---|---|---|
| `contact_messages` | `user_id` nullable FK (null on delete), `name`, `phone`, `email` nullable, `subject`, `message`, `status` | index `status`, `created_at` |

## 8. Routes / API
| Method | URI | Name | Controller@action | Auth |
|---|---|---|---|---|
| GET | `/about`, `/faq`, `/privacy-policy`, `/terms`, `/shipping-policy`, `/return-policy` | `about`, `faq`, `privacy`, `terms`, `shipping`, `returns` | `PageController@show` | public |
| GET | `/page/{slug}` | `page` | `PageController@show` | public |
| GET | `/contact` | `contact` | `ContactController@show` | public |
| POST | `/contact` | `contact.store` | `ContactController@store` | public, `throttle:5,10` |

## 9. Security requirements
- Markdown rendered safely (no raw HTML, no unsafe links); all form input validated; CSRF; rate limit + honeypot.

## 10. Edge cases
- Unpublished or unknown page → 404; system page under `/page/…` → 404
- FAQ without any `##` heading → intro only
- Phone with spaces / +880 → normalised; invalid → error
- Bot fills honeypot → success shown, nothing saved
- 6th message within 10 minutes → 429

## 11. Acceptance criteria
- [x] System pages and extra pages render from the database; drafts 404
- [x] FAQ accordion from `##` headings; policy pages show last updated date
- [x] Contact channels from settings
- [x] Message saved (validation, phone normalised, user linked, status new)
- [x] Honeypot and rate limit
- [x] **Milestone M2**

## 12. Multi-vendor impact
None. A later "contact the seller" would be a separate feature.

## 13. Open questions
- [x] URL for extra pages → `/page/{slug}` (user, 2026-10-07)
- [x] Email the store on new messages? → no, inbox only (user, 2026-10-07)
