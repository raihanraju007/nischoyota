# Roadmap

Feature order, scope summary and status. Detailed specs and steps live in `features/F-NNN-*/` and are
written with `/feature-new` **when a feature starts**, not before. The briefs below are the starting
point for each `feature.md`. They are not the approved spec.

Status: `[ ]` not started · `[~]` in progress · `[x]` done · `[-]` dropped

## How to work through this list

1. Take the **next unchecked item from the top**. Do not skip ahead: each stage needs the data of the stage before it.
2. Gates (`G-*`) are decisions. Answer them before the features that depend on them.
3. Every feature (`F-*`) goes through the loop in [workflow.md](workflow.md):
   `/feature-new` → `/feature-explore` → you approve → `/feature-implement` → `/feature-review` → `/sync-docs` → commit.
4. **Making a static page dynamic:** keep the markup of the existing Blade view and replace only the hard-coded
   arrays with controller data. The page must still look like the prototype.
5. **No admin design exists yet** for the screens marked 🎨. Design them first, inside the feature, with the
   existing admin components (`x-layouts.admin`, `.admin-table`, `x-form.*`, `x-modal`). Do not start a new style.

### Why this order

```
Stage 0  Decisions + foundation      ── nothing can be built without them
Stage 1  Admin login                 ── protects everything after it
Stage 2  Admin: set up the store     ── creates the data the storefront shows
Stage 3  Storefront: customers       ── login, account, addresses
Stage 4  Storefront: browsing        ── reads the Stage 2 data
Stage 5  Storefront: buying          ── cart, checkout, creates orders
Stage 6  Admin: daily operations     ── orders, customers, reviews, dashboard need Stage 5 data
Stage 7  Launch readiness
```

The admin panel is split in two on purpose. Stage 2 admin screens **create** data (products, categories…),
so they come before the storefront. Stage 6 admin screens **manage customer data** (orders, reviews), which only
exists after checkout works. Building them earlier would mean testing against fake data and rewriting later.

## Milestones

| Milestone | After | You can… |
|---|---|---|
| **M1 — Admin can run the catalog** ✅ reached 2026-10-06 | F-012 | Log in to `/admin`, create categories, brands, products with variants, images and stock, set delivery charges, coupons and policy pages |
| **M2 — Customers can browse** | F-020 | Register, log in and browse real products, search, filter, open product pages |
| **M3 — Customers can buy** | F-024 | Add to cart, check out with COD, track and cancel orders |
| **M4 — Store can operate** ✅ reached 2026-10-07 | F-028 | Process orders from the admin panel, moderate reviews, see sales on the dashboard |
| **M5 — Launch** | F-031 | Go live |

---

## Phase 0: Foundation

- [x] Laravel skeleton, Pest, Pint, Boost
- [x] Static storefront Blade views from the prototype
- [x] Static admin panel Blade views
- [x] AI workflow and docs structure

### Stage 0: Decisions (gates)

- [x] **G-1 Approve the database design.** Review [database.md](database.md) §2 (D1–D9) and record them as ADRs.
- [x] **G-2 Approve the module map** (below) and record it as an ADR.
- [ ] **G-3 Fill in `prd.md`**: vision, users, and §5 business rules (BDT only, COD only in Phase 1?, return window, English UI + Bangla location names?).
- [x] **G-4 Auth approach.** Recommendation: build it with Laravel's own `Auth`, sessions and `RateLimiter`, no Breeze/Fortify. Phone login and OTP do not fit their defaults, and the views already exist.
- [x] **G-5 Store settings storage.** The admin Settings page edits store name, phone, email, address, and the free-delivery threshold. `database.md` puts the threshold in `config/shop.php`, which an admin cannot edit. Options: a `settings` key/value table (recommended) or config only.
- [ ] **G-6 SMS provider** (SSL Wireless, Alpha SMS, BulkSMSBD…). F-014 runs on the `log` driver (`SmsGateway` interface, `SMS_DRIVER=log`); add the real provider as a new driver before launch.
- [x] **G-7 Image storage.** Recommendation: `public` disk + `storage:link` now, with no image package. Resize/optimise in F-030.
- [x] **G-8 Admin roles.** Is `staff` limited (e.g. no settings, no coupons, no cost prices)? Define it before F-002.

#### Proposed module map (for G-2)

| Module | Owns | Built in |
|---|---|---|
| `Account` | users, roles, auth, OTP, addresses | F-002, F-014, F-015 |
| `Location` | divisions, districts, upazilas, delivery zones | F-009 |
| `Catalog` | categories, brands, attributes, products, variants, images | F-003 – F-007 |
| `Inventory` | stock movements, stock adjust / reserve / release | F-008 |
| `Shopping` | cart, wishlist | F-021, F-022 |
| `Promotion` | coupons and coupon validation | F-011 |
| `Order` | orders, items, status history, payments, checkout | F-023 – F-025 |
| `Review` | reviews and rating cache | F-027 |
| `Content` | CMS pages, contact messages, store settings | F-010, F-012, F-020 |
| `Dashboard` | read-only admin reports (calls other modules' services) | F-028 |

### F-001 Foundation (module pattern)

- [x] **F-001 Module foundation** ([spec](features/F-001-module-foundation/feature.md)): sets the pattern every module copies.
  - Module folder layout + `ModuleServiceProvider` that loads each module's `routes/web.php` and `routes/admin.php`
  - Route groups: storefront (`web`), admin (`/admin`, `admin.` names, `auth` + `admin` middleware)
  - `Money` handling: paisa ↔ taka cast + one Blade formatter for `৳4,850` (rule D4)
  - Shared pieces: base enum conventions, flash/toast messages, validation error display in `x-form.*`
  - Test helpers: `actingAsAdmin()`, `actingAsCustomer()`
  - Demo `DatabaseSeeder` structure (each later feature adds its own seeder)
  - **Done when:** one example route in a module renders through Controller → Service → Repository, with a passing test

---

## Phase 1: Single vendor

### Stage 1: Admin authentication

- [x] **F-002 Admin authentication & access** ([spec](features/F-002-admin-authentication/feature.md))
  - **Module:** Account · **Depends:** F-001, G-1, G-4, G-8
  - **Tables:** change `users` (phone, role, status, last_login_at, phone_verified_at)
  - **Screens:** `admin/auth/login` (exists), admin layout top bar (user name, logout), Settings → "Change password" tab
  - **Tasks:**
    - `UserRole` and `UserStatus` enums, `users` migration change, `UserFactory` states (admin, staff, customer, blocked)
    - Login with phone **or** email + password, "remember me", logout
    - `EnsureUserIsAdmin` middleware: only `admin`/`staff`, blocked users rejected
    - Login rate limit (e.g. 5 tries/minute per phone + IP)
    - Artisan command `app:create-admin` + seeder for the first admin
    - Move all `/admin` routes behind the middleware (pages stay static for now)
    - Admin change-password form
  - **Done when:** a guest who opens any `/admin/*` URL is sent to `/admin/login`, a customer gets 403, an admin gets in, and tests cover all three

### Stage 2: Admin, setting up the store

- [x] **F-003 Categories** ([spec](features/F-003-categories/feature.md))
  - **Module:** Catalog · **Depends:** F-002
  - **Tables:** `categories`
  - **Screens:** `admin/categories/index` (exists), create/edit form (modal or page)
  - **Tasks:** CRUD, parent/child (2 levels), unique slug auto-generated from the name, image upload, sort order, active toggle, SEO fields, block deleting a category that has products or children
  - **Done when:** an admin can build the category tree that the storefront menu will show

- [x] **F-004 Brands** 🎨 ([spec](features/F-004-brands/feature.md))
  - **Module:** Catalog · **Depends:** F-002
  - **Tables:** `brands`
  - **Screens:** new `admin/brands/index` + form (sidebar link)
  - **Tasks:** CRUD, logo upload, slug, active toggle, block deleting a brand in use

- [x] **F-005 Attributes (Color, Size, Wattage)** 🎨 ([spec](features/F-005-attributes/feature.md))
  - **Module:** Catalog · **Depends:** F-002
  - **Tables:** `attributes`, `attribute_values`
  - **Screens:** new `admin/attributes` page: attributes with their values, colour swatch picker
  - **Tasks:** CRUD for attributes and values, `color_hex`, sort order, seed Color and Size

- [x] **F-006 Products (core)** ([spec](features/F-006-products/feature.md))
  - **Module:** Catalog · **Depends:** F-003, F-004
  - **Tables:** `products`, `product_variants` (default variant only), `product_images`
  - **Screens:** `admin/products/index` (exists: list, tabs by status, search, filter by category), `admin/products/form` (exists: create **and** edit)
  - **Tasks:**
    - Create/edit a simple product: name, slug, category, brand, descriptions, specifications (label/value rows), status, featured, pre-order, SEO
    - Default variant: SKU, price, compare price, cost price, stock, weight
    - Multiple image upload, sort order, main image, delete image
    - Cached columns (`min_price`, `max_discount_percent`) updated by the service
    - List: paginate, search by name/SKU, filter by status/category, bulk publish/archive
    - Product seeder with realistic demo data from the prototype
  - **Done when:** a product created in the admin has everything the product page will need

- [x] **F-007 Product variants** ([spec](features/F-007-product-variants/feature.md))
  - **Module:** Catalog · **Depends:** F-005, F-006
  - **Tables:** `attribute_value_product_variant`, image ↔ variant link
  - **Screens:** "Variants" section in the product form
  - **Tasks:** pick attributes → generate variant combinations, per-variant SKU/price/stock/active, choose the default variant, colour-specific images, keep cached product columns in sync

- [x] **F-008 Inventory** 🎨 ([spec](features/F-008-inventory/feature.md))
  - **Module:** Inventory · **Depends:** F-006
  - **Tables:** `stock_movements`
  - **Screens:** new `admin/inventory`: stock list with low-stock filter, "Adjust stock" modal, movement history per variant
  - **Tasks:** `InventoryService` (adjust, decrease, increase), every change writes a `stock_movements` row, low-stock threshold, stock can never go negative
  - **Done when:** checkout (F-023) can call `InventoryService` instead of touching stock itself

- [x] **F-009 Locations & delivery zones** 🎨 ([spec](features/F-009-locations-delivery-zones/feature.md))
  - **Module:** Location · **Depends:** F-002, G-1
  - **Tables:** `divisions`, `districts`, `upazilas`, `delivery_zones`
  - **Screens:** Settings → new "Delivery" tab: zone name, charge, min/max days, active; district/upazila → zone mapping
  - **Tasks:** seed 8 divisions / 64 districts / ~495 upazilas with Bangla names, seed 3 zones, `DeliveryChargeService` (zone of an upazila → charge + days, free-delivery threshold), JSON endpoints for dependent dropdowns
  - **Done when:** given an upazila and a subtotal, the service returns the correct charge and delivery estimate

- [x] **F-010 Store settings** ([spec](features/F-010-store-settings/feature.md))
  - **Module:** Content · **Depends:** F-002, G-5
  - **Screens:** `admin/settings` (exists): General tab (name, support phone/email, address, social links, free-delivery threshold)
  - **Tasks:** settings storage + cached reader used by the header/footer/checkout

- [x] **F-011 Coupons** ([spec](features/F-011-coupons/feature.md))
  - **Module:** Promotion · **Depends:** F-002
  - **Tables:** `coupons`
  - **Screens:** `admin/coupons/index` (exists) + create/edit form
  - **Tasks:** CRUD, fixed/percent, min order, max discount, usage limits, dates, active toggle, `CouponService::validate(code, subtotal, user)` returning the discount or a clear error reason
  - **Done when:** every coupon rule is a unit test (expired, not started, below minimum, limit reached, per-user limit, percent cap)

- [x] **F-012 CMS pages** 🎨 ([spec](features/F-012-cms-pages/feature.md))
  - **Module:** Content · **Depends:** F-002
  - **Tables:** `pages`
  - **Screens:** new `admin/pages` list + editor (title, slug, body, SEO, published)
  - **Tasks:** CRUD, seed the 7 existing pages (about, faq, privacy, terms, shipping, returns) from the current static text
  - **Milestone M1 reached**

### Stage 3: Storefront, customers

- [x] **F-013 Storefront shell (dynamic layout)** ([spec](features/F-013-storefront-shell/feature.md))
  - **Depends:** F-003, F-010
  - **Screens:** `components/layout/header`, `mobile-menu`, `footer`, `bottom-nav`
  - **Tasks:** category menu from the database, store contact info from settings, logged-in/guest state in the header, cart/wishlist count placeholders (filled in F-021/F-022), cache the menu

- [x] **F-014 Customer authentication** ([spec](features/F-014-customer-auth/feature.md))
  - **Module:** Account · **Depends:** F-002, G-6
  - **Tables:** `otp_codes`
  - **Screens:** `auth/login`, `auth/register`, `auth/forgot-password`, `auth/reset-password` (all exist) + an OTP entry step
  - **Tasks:**
    - Register with name, phone, optional email, password → phone OTP → verified
    - Login with phone or email; blocked users cannot log in
    - Forgot password: by phone (OTP) or by email (reset link)
    - `OtpService`: hashed codes, 5-minute expiry, 5 attempts, send rate limit (1/60 s, 5/hour)
    - `SmsSender` interface + `log` driver (real provider after G-6)
    - Phone normalisation (`+880…`, `880…` → `01…`)
    - Redirect to the intended page after login
  - **Done when:** the full register → OTP → login → forgot → reset cycle passes in tests, including OTP abuse cases

- [x] **F-015 Customer account & addresses** ([spec](features/F-015-account-addresses/feature.md))
  - **Module:** Account · **Depends:** F-009, F-014
  - **Tables:** `addresses`
  - **Screens:** `account/dashboard`, `account/profile`, `account/addresses` (exist)
  - **Tasks:** edit profile, change password, change phone (with OTP), address book CRUD with Division → District → Upazila dropdowns, default address, policy: users only see their own addresses

### Stage 4: Storefront, browsing

- [x] **F-016 Product listing, filters and sorting** ([spec](features/F-016-product-listing/feature.md))
  - **Module:** Catalog · **Depends:** F-007, F-013
  - **Screens:** `shop/index`, `shop/category`, `shop/deals` (exist), `components/shop/filters`, `sort-select`, `product-card`, `pagination`
  - **Tasks:** real routes `/shop`, `/category/{slug}`, `/deals`; filters (category, brand, price range, colour, size, rating, discount, pre-order, in stock) as query strings; sort (newest, price, popular, rating); pagination; only `active` products; avoid N+1 queries

- [x] **F-017 Product detail page** ([spec](features/F-017-product-detail/feature.md))
  - **Module:** Catalog · **Depends:** F-007
  - **Screens:** `products/show` (exists), `quick-view-modal`
  - **Tasks:** route `/product/{slug}`, gallery, variant picker (price/stock/image change with the variant), specs, stock status, related products, breadcrumbs, SEO meta, 404 for draft/archived products, reviews section shows approved reviews (filled in F-027)

- [x] **F-018 Search** ([spec](features/F-018-search/feature.md))
  - **Module:** Catalog · **Depends:** F-016
  - **Tables:** `FULLTEXT(name)` index on `products`
  - **Screens:** `shop/search`, `shop/search-empty` (exist), header search box
  - **Tasks:** search by name/SKU/brand, reuse the listing filters, empty state, header suggestions (optional)

- [x] **F-019 Home page** → [spec](features/F-019-home-page/feature.md)
  - **Depends:** F-016
  - **Screens:** `home` (exists)
  - **Tasks:** categories, featured products, deals with countdown, new arrivals, best sellers, promo banners (static or from settings), cache the sections

- [x] **F-020 Content pages & contact form** → [spec](features/F-020-content-pages-contact/feature.md)
  - **Module:** Content · **Depends:** F-012
  - **Tables:** `contact_messages`
  - **Screens:** `pages/*`, `pages/contact` (exist)
  - **Tasks:** render pages from the database by slug, contact form saves a message (rate limited, honeypot)
  - **Milestone M2 reached** (2026-10-07)

### Stage 5: Storefront, buying

- [x] **F-021 Cart** ([spec](features/F-021-cart/feature.md))
  - **Module:** Shopping · **Depends:** F-017, F-014
  - **Tables:** `carts`, `cart_items`
  - **Screens:** `cart/index`, `cart/empty`, `layout/cart-drawer`, `qty-control`, header cart count
  - **Tasks:** guest cart by session, user cart, merge guest cart on login, add/update/remove, stock check on every change, prices always read live from the variant, coupon preview, delivery estimate

- [x] **F-022 Wishlist** ([spec](features/F-022-wishlist/feature.md))
  - **Module:** Shopping · **Depends:** F-017, F-014
  - **Tables:** `wishlist_items`
  - **Screens:** `wishlist/index`, `wishlist/empty`, heart button on `product-card`
  - **Tasks:** toggle, list, move to cart, header count; guests are asked to log in

- [x] **F-023 Checkout (Cash on Delivery)** ([spec](features/F-023-checkout/feature.md))
  - **Module:** Order · **Depends:** F-008, F-009, F-011, F-021
  - **Tables:** `orders`, `order_items`, `order_status_histories`, `payments`
  - **Screens:** `checkout/index`, `checkout/success` (exist)
  - **Tasks:**
    - Guest and logged-in checkout, choose a saved address or enter a new one
    - Server recalculates subtotal, coupon, delivery charge and total. It never trusts browser prices.
    - One DB transaction: create order + items (snapshots) + status history + COD payment row, decrease stock via `InventoryService`, record coupon use, clear the cart
    - Guest with an unverified phone → OTP before the order is confirmed (D2)
    - Order number generator (`ORD-10025`), protection against double submit
    - `OrderPlaced` event (used by F-029)
  - **Done when:** tests cover stock running out mid-checkout, an invalid coupon, a price changed after add-to-cart, and the guest OTP

- [x] **F-024 Customer orders & tracking** ([spec](features/F-024-customer-orders/feature.md))
  - **Module:** Order · **Depends:** F-023
  - **Screens:** `account/orders/index`, `account/orders/show`, `account/orders/empty`, `orders/track` (exist), `x-order.*` components
  - **Tasks:** order history with pagination, order detail + status timeline, guest tracking by order number + phone, cancel while `pending`/`confirmed` (stock restored), "Buy again"
  - **Milestone M3 reached** (2026-10-07)

### Stage 6: Admin, daily operations

- [x] **F-025 Admin order management** ([spec](features/F-025-admin-orders/feature.md))
  - **Module:** Order · **Depends:** F-024
  - **Screens:** `admin/orders/index`, `admin/orders/show` (exist), sidebar new-order badge
  - **Tasks:** list with status tabs + counts, search by order number/phone, date filter; detail page; change status with allowed transitions only (`OrderStatus` state rules); a status history row on every change; courier name + tracking code; admin note; mark COD paid; cancel/return → restock + refund payment row

- [x] **F-025b Print packing slip & order copy** ([spec](features/F-025b-order-printing/feature.md)) (requested 2026-10-06)
  - **Module:** Order · **Depends:** F-025, F-010 (store name/phone/address on the print)
  - **Screens:** print-friendly A4 pages (browser "Print / Save as PDF", no package); print buttons on `admin/orders/show`; bulk print from `admin/orders/index`
  - **Packing slip / courier label** — goes on the parcel box: store name + phone (sender), customer name, phone, full address (division → district → upazila), order number in large text, COD amount to collect (৳, or "PAID"), item count, weight, courier name + tracking code, delivery note. Half an A4, so two slips per page.
  - **Order copy (invoice)** — goes inside the box / for the customer: store details, order number and date, customer + shipping address, items (name, variant, SKU, qty, unit price, line total), subtotal, discount, delivery charge, total, payment method/status, return policy line, thank-you note.
  - **Bulk print** packing slips for selected orders (e.g. all "Confirmed" orders before courier pickup); printing a slip can optionally move the order to "Processing"

- [x] **F-026 Admin customers & messages** ([spec](features/F-026-admin-customers-messages/feature.md))
  - **Module:** Account, Content · **Depends:** F-024, F-020
  - **Screens:** `admin/customers/index` (exists) + customer detail; new `admin/messages` inbox
  - **Tasks:** customer list/search, detail with orders and addresses, block/unblock (fake-order abusers), order count and total spent; contact messages: list, mark replied/closed; (optional) staff user management

- [x] **F-027 Reviews** ([spec](features/F-027-reviews/feature.md))
  - **Module:** Review · **Depends:** F-024
  - **Tables:** `reviews`
  - **Screens:** review form on `products/show`, `components/product-review`, `review-card`, `admin/reviews/index` (exists)
  - **Tasks:** one review per user per product, "Verified purchase" when there is a delivered order item, admin approve/reject, rating cache on the product updated on approve/reject

- [x] **F-028 Admin dashboard & reports** ([spec](features/F-028-admin-dashboard/feature.md))
  - **Module:** Dashboard · **Depends:** F-025
  - **Screens:** `admin/dashboard` (exists), `x-admin.stat-card`, `x-admin.sales-chart`
  - **Tasks:** today/month sales, order count, new customers, orders by status, sales chart (7/30 days), recent orders, low stock list; cache the queries
  - **Milestone M4 reached** (2026-10-07)

### Stage 7: Launch readiness

- [ ] **F-029 Notifications**: SMS/email to the customer on order placed, confirmed, shipped, delivered and cancelled; admin email on new order; queued jobs. Depends on G-6.
- [ ] **F-030 SEO & performance**: revisit search (MySQL FULLTEXT or a search engine — F-018 uses word-by-word LIKE), meta tags, Open Graph, `sitemap.xml`, `robots.txt`, image resize/WebP, query and page caching, a pass over N+1 queries.
- [ ] **F-031 Security & go-live**: rate-limit review, error pages (`errors/404`, `500` exist), logging, backups, queue worker, `deployment.md`, production checklist.
  - **Milestone M5 reached**

### After launch (needs an ADR first)

- [ ] Online payment (bKash / Nagad / SSLCommerz)
- [ ] Courier API integration (Pathao / Steadfast / RedX)
- [ ] Returns as a separate flow

## Phase 2: Multi-vendor (future)

Not planned in detail. See `prd.md` §8.

## Phase 3: Dropshipping (future)

Not planned in detail.
