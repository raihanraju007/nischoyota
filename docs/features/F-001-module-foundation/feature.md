# F-001: Module foundation

- **Status:** Done
- **Module:** none (shared infrastructure: `app/Providers`, `app/Support`, shared Blade components)
- **Phase:** 1
- **Depends on:** ADR-001, ADR-003, ADR-007, ADR-008

## 1. Objective
Give every later module the same way to register its routes, format money and show flash messages,
so F-002 onwards only adds business code.

## 2. User stories
- As a **developer**, I want a module to register its storefront and admin routes with one call, so every module is wired the same way.
- As a **customer/admin**, I want prices shown the same way everywhere (`৳ 4,850`), and a toast after I save something.

## 3. Business rules
1. Money is stored as integer paisa (D4). Display: `৳ ` + thousands separators; decimals only when the amount has paisa (`৳ 4,850`, `৳ 4,850.50`).
2. Taka input from forms (`"4850"`, `"4850.5"`) converts to paisa by rounding to the nearest paisa; negative amounts are rejected.
3. Admin routes are prefixed `/admin` and named `admin.*`. Storefront routes use the `web` middleware group.
4. Flash keys `success`, `error`, `info` become a toast on the next page, in all three layouts.
5. Outside production, lazy loading and silently discarded attributes throw (N+1 and mass-assignment bugs show up in tests).

## 4. Scope
- `App\Providers\ModuleServiceProvider` base class with `loadModuleRoutes()`
- `App\Support\Money` (format, taka → paisa, paisa → taka input value)
- `<x-money :amount="$paisa" />` component
- `<x-flash-toasts />` component in the storefront, admin and admin-auth layouts + JS hook
- Model strictness in `AppServiceProvider`
- Docs: "Adding a module" in `architecture.md`

## 5. Out of scope
- Any business module (first one is `Account` in F-002)
- Auth / admin middleware (F-002), test helpers that need roles (F-002)
- Replacing existing `number_format()` price output in static views (each feature does it when it makes the page dynamic)

## 6. UI requirements
- Toasts reuse the existing `.toast` styles and `window.dmToast()` in `resources/js/storefront.js`.

## 7. Database requirements
None.

## 8. Routes / API
None of its own. Test fixture routes only.

## 9. Security requirements
- Flash messages are passed through `textContent` (no HTML injection).

## 10. Edge cases
- `Money::format(0)` → `৳ 0`; `Money::fromTaka("12.345")` → `1235` (round half up); negative input → `InvalidArgumentException`.

## 11. Acceptance criteria
- [x] A module provider that calls `loadModuleRoutes()` registers its `web.php` routes and its `admin.php` routes under `/admin` with `admin.` names
- [x] `Money` formats and converts as in §3 and §10
- [x] A flashed `success` message appears on the next page as a toast
- [x] Lazy loading throws in tests

## 12. Multi-vendor impact
None. A future `Vendor` module registers routes the same way.

## 13. Open questions
- None.
