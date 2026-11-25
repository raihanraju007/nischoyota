# F-013: Storefront shell (dynamic layout)

- **Status:** Done
- **Module:** shared (`App\Http\View\Composers`), reads Catalog + Content + Account through their services
- **Phase:** 1
- **Depends on:** F-003, F-010

## 1. Objective
Every storefront page shows the real category menu, store contact details, free-delivery message and the visitor's
login state, instead of hard-coded text.

## 2. User stories
- As a **customer**, I want the menu to show the store's real departments and subcategories.
- As the **owner**, I want phone/email/address changes in Settings to appear on the site immediately.

## 3. Business rules
1. Menu = **active** departments (top-level categories) in sort order; each with its **active** subcategories. Hidden categories never appear.
2. Desktop nav: up to 5 departments + Deals. Mega menu: all departments with subcategories + Deals. Mobile menu: all departments.
3. Category links go to `/category/{slug}` (route `category`, slug optional; the page itself becomes dynamic in F-016).
4. Top bar: "Cash on Delivery Available Nationwide — Free Delivery on Orders Over ৳ X" (X = setting); without a threshold only the COD part.
5. Footer: Shop column = first 5 departments + Today's Deals; Contact column = support phone, hours, email, address from Settings; © year + store name.
6. Header account link: guest → "Account" (login); signed-in user → first name, linking to My Account. Mobile menu shows Login / Register or My Account accordingly.
7. Cart and wishlist badges are shown only when the count is above 0 (counts come from F-021 / F-022; 0 until then).
8. The menu is cached and refreshed whenever a category is created, updated or deleted.

## 4. Scope
- `StorefrontShellComposer` for `layout.header`, `layout.footer`, `layout.mobile-menu`; registered in `AppServiceProvider`
- `CategoryService::menuTree()` (cached) + cache clearing on category changes
- `/category/{slug?}` route; header, footer, mobile menu, top bar use real data

## 5. Out of scope
- Home page sections (F-019), category/shop pages (F-016), search suggestions (F-018), contact page text (F-020), real cart/wishlist counts (F-021/F-022)

## 11. Acceptance criteria
- [x] Header nav, mega menu, mobile menu and footer list active categories from the database
- [x] Settings values in top bar and footer
- [x] Guest vs signed-in header
- [x] Badges hidden at 0
- [x] Menu cache cleared on category save/delete
- [x] All existing storefront pages still render

## 12. Multi-vendor impact
None.

## 13. Open questions
- None.
