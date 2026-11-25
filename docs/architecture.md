# Architecture

## Stack

| Layer | Choice |
|---|---|
| Language | PHP 8.3 |
| Framework | Laravel 13 |
| Database | MySQL 8 |
| Frontend | Blade + Tailwind CSS 4 (Vite) |
| Tests | Pest 4 |
| Formatter | Laravel Pint |

## Style: modular monolith

One Laravel app, split into business modules under `app/Modules/`. Each module owns its
models, services, repositories, controllers, requests and policies. See [ADR-001](decisions/ADR-001-modular-monolith.md).

```
app/
├── Modules/
│   └── Catalog/                      ← one folder per business module
│       ├── Http/
│       │   ├── Controllers/          ← thin: request in, response out
│       │   │   └── Admin/            ← admin-side controllers
│       │   ├── Requests/             ← FormRequest: validation, authorization, toData()
│       │   └── Resources/            ← (optional) JSON API Resources
│       ├── Models/                   ← Eloquent models, relations, casts, scopes
│       ├── Repositories/             ← all database queries
│       ├── Services/                 ← business logic, transactions
│       ├── Policies/                 ← authorization
│       ├── Data/                     ← readonly DTOs: validated input → service
│       ├── Enums/                    ← backed enums (statuses, types)
│       ├── Exceptions/               ← domain exceptions (InsufficientStockException)
│       ├── Events/ Listeners/        ← (optional) cross-module communication
│       ├── Providers/
│       │   └── CatalogServiceProvider.php
│       └── routes/
│           ├── web.php
│           └── admin.php
├── Http/Controllers/Controller.php   ← base controller
└── Providers/
```

Outside `app/`, Laravel defaults are kept:

| What | Where |
|---|---|
| Migrations | `database/migrations/` |
| Factories / seeders | `database/factories/`, `database/seeders/` |
| Views | `resources/views/<area>/` (existing storefront layout) |
| Tests | `tests/Feature/<Module>/`, `tests/Unit/<Module>/` |

## Request flow

```
Route ─▶ Controller ─▶ FormRequest (validate + authorize)
              │
              ▼
           Service   ── business rules, DB transactions, events
              │
              ▼
          Repository ── Eloquent queries only
              │
              ▼
            Model ─▶ MySQL
              │
Controller ◀──┘  returns Blade view / redirect / JSON Resource
```

Rules for each layer are in [rules.md](rules.md#layers).

## Modules

Agreed map: [ADR-008](decisions/ADR-008-module-map.md). Status changes as features land.

| Module | Responsibility | Status |
|---|---|---|
| `Account` | users, roles, auth, OTP, addresses | Built: admin auth (F-002), customer auth + OTP (F-014), account + addresses (F-015), admin customers + block (F-026; `CustomerService` reads order stats through `CustomerOrderService`) |
| `Location` | divisions, districts, upazilas/thanas, delivery zones, `DeliveryChargeService` | Built: F-009 |
| `Catalog` | categories, brands, attributes, products, variants, images | Built: categories (F-003), brands (F-004), attributes (F-005), products (F-006), variants (F-007), storefront listing (F-016), product page (F-017), search (F-018), home page (F-019) |
| `Inventory` | stock movements; only writer of `product_variants.stock_quantity` (ADR-013) | Built: F-008 |
| `Shopping` | cart, wishlist | Built: cart (F-021), wishlist (F-022); `Http\CurrentCart` / `Http\CurrentWishlist` hold per-request state (inject per action, not in constructors) |
| `Promotion` | coupons, `CouponService::apply()` | Built: F-011 |
| `Order` | checkout, orders, payments | Built: checkout (F-023), customer orders + tracking (F-024), admin orders (F-025), printing (F-025b); `OrderService::place()` creates orders, `OrderStatusService` is the only place that changes status / payment (customer cancel included), `AdminOrderService` / `CustomerOrderService` read, `OrderPrintService` prints, `OrderReportService` feeds the dashboard |
| `Review` | reviews, rating cache | Built: F-027; writes the product rating cache through `ProductService::updateRatingCache()` |
| `Content` | CMS pages, contact messages, store settings (`SettingService::current()`) | Built: settings (F-010), pages (F-012), storefront pages + contact messages (F-020), admin inbox (F-026) |
| `Dashboard` | admin reports | Built: F-028; read-only, only calls `OrderReportService`, `CustomerService`, `InventoryService`; numbers cached 5 min |

### Shared code (not a module)

| What | Where |
|---|---|
| Module route loading | `App\Providers\ModuleServiceProvider` (abstract) |
| Phone normalisation (`+880 1712-345678` → `01712345678`) | `App\Support\PhoneNumber` |
| Tidying text input in `prepareForValidation()` (array-safe: `phone[]=1` fails validation, not a 500) | `App\Support\NormalizesStringInput::normalizeStrings(['phone' => fn (string $phone) => …])` |
| Money (paisa ↔ taka, `৳` display) | `App\Support\Money`, Blade `<x-money :amount="$paisa" />` |
| Flash toasts (`success`, `error`, `info`) | `<x-flash-toasts />` in all layouts; `return back()->with('success', __('Saved.'))` |
| Confirm-and-submit dialogs | `<x-confirm-modal method="DELETE">` + `data-modal-action="{url}"` on each trigger |
| Select with value => label options | `<x-form.select :options="[3 => 'Electrical']" placeholder="None">` |
| Image uploads (store / replace / remove / delete on the public disk) | `App\Support\PublicImageStorage` |
| Unique slugs (`fans`, `fans-2`…) | `App\Support\UniqueSlug::generate()` |
| Pagination | `{{ $paginator->links() }}` renders `partials/pagination.blade.php` (default view, `.pagination` style) |
| Repeater rows (add/remove form rows) | `data-repeater` + `<template data-repeater-template>` with `__INDEX__` (storefront.js) |
| Select-all checkbox | `data-check-all="ids[]"`; row checkboxes can live outside the form via `form="bulk-form"` |
| Safe Markdown (pages, product descriptions) | `App\Support\SafeMarkdown::toHtml()` (HTML stripped, no unsafe links) |
| Storefront shell data (menu, settings, signed-in name, cart/wishlist counts) | `App\Http\View\Composers\StorefrontShellComposer` on `layout.header/footer/mobile-menu/bottom-nav`; `CartDrawerComposer` on `layout.cart-drawer`; `WishlistHeartComposer` on `product-card` and `products.show` |
| SMS sending | `App\Modules\Account\Sms\SmsGateway` (drivers `log`, `array`; `config('services.sms.driver')`) |
| Division → District → Upazila selects | `<x-address-fields>` (server-filled options + `data-location-*` JS using `/locations` JSON) |
| Model strictness (lazy loading, discarded attributes throw outside production) | `AppServiceProvider` |
| Admin sidebar badges (pending orders, new messages, pending reviews) | `App\Http\View\Composers\AdminShellComposer` on `components.layouts.admin` |
| Product page reviews / home testimonials | `ProductReviewsComposer` on `products.show`, `HomeReviewsComposer` on `home` (Catalog does not depend on Review) |
| Print pages (A4, no chrome, "Print / Save as PDF") | `<x-layouts.print>` + `admin.css` §13 |

### Adding a module

1. Create `app/Modules/<Module>/Providers/<Module>ServiceProvider.php` extending `App\Providers\ModuleServiceProvider`:
   ```php
   final class CatalogServiceProvider extends ModuleServiceProvider
   {
       public function boot(): void
       {
           $this->loadModuleRoutes(__DIR__.'/../routes');
       }
   }
   ```
2. Add it to `bootstrap/providers.php`.
3. Storefront routes go in `routes/web.php` (`web` middleware). Admin routes go in `routes/admin.php`
   (automatically under `/admin`, names prefixed `admin.`, middleware `web` + `auth` + `admin`). Either file is optional.
   Admin-only pages (ADR-012) add `->middleware('can:manage-store')`.
4. Delete the matching `Route::view()` lines from the root `routes/web.php` when a page becomes dynamic.

## Cross-module communication

- A module may call **another module's Service**, never its Repository or Model queries directly.
- Exception ([ADR-013](decisions/ADR-013-inventory-owns-stock.md)): `Inventory` reads Catalog's product/variant tables and is the only writer of `product_variants.stock_quantity`.
- `CartItem` has a read-only `belongsTo` to Catalog's `ProductVariant` for live name/price/stock; cart writes look variants up through `ProductVariantService::findForCart()`.
- `Address` has read-only `belongsTo` relations to `Location` models to show place names; writes to locations go through `Location` only.
- Checkout (F-023) uses other modules only through their services: `CartService`, `InventoryService::deductForOrder()`, `CouponService::apply()/recordUse()`, `DeliveryChargeService`, `LocationService`, `AddressService`, `OtpService`.
- `Review` has read-only `belongsTo` relations to `Product` and `User` to show names; product rating columns are written only through Catalog's `ProductService`.
- Blocked accounts: `OrderService::place()` asks `UserService::isBlockedFromOrdering()` (account or phone).
- Prefer events when the caller does not need a result (e.g. `OrderPlaced` → Inventory reserves stock).

## Multi-vendor readiness (Phase 1 constraints)

See [ADR-006](decisions/ADR-006-vendor-agnostic-core.md). Phase 1 does not build vendor features, but:

- Seller-specific logic is not hard-coded into unrelated modules.
- TODO: record concrete constraints as they are agreed (e.g. where a future `vendor_id` would attach).

## Current state

Every page is served by a module; the root `routes/web.php` has no routes left (Stage 6, 2026-10-07).
The source HTML prototype is the `nischoyota-frontend/` git submodule.
