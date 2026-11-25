# Current Context

> Only what is true **now**. Move permanent facts to their proper file (see [README.md](README.md)) and delete stale lines.
> Last updated: 2026-10-07

## Current phase
Phase 1, Stage 7: Launch readiness (Stage 6 done; **Milestone M4 reached** 2026-10-07)

## Current feature
None. Next: F-029 Notifications (needs G-6, SMS provider).

## Current work
Every page is now dynamic; the root `routes/web.php` has no routes left.
Still open: G-3 (PRD business rules), G-6 (SMS provider — running on the `log` driver; codes appear in `storage/logs/laravel.log`).

## Recently completed
- Bangla + English UI, **Bangla default** ([ADR-014](decisions/ADR-014-bangla-default-language.md)): language switcher (cookie), `lang/bn.json`, Bangla digits, storefront, account and admin (print slips stay English). **Clothing paused** (focus on food and plants) via `SHOP_CLOTHING_ENABLED=false`; code kept, re-enable in ~2 months (2026-11-25)
- Rebranded from the GSS Bazar codebase to **Nischoyota (নিশ্চয়তা)** — honey, ghee, natural foods, clothing, plants. Brand colours from the logo (forest green #2f6b3a, honey gold #d4921f, cream). Logo/photos from the Facebook page in `public/images/brand/fb/`. New product field: selling unit (`sell_unit` + `unit_quantity`, `SellUnit` enum) on the product form, shown as "৳450 / 500 g". SKU prefix NS-. (2026-11-25)
- E2E hardening pass (HTTP-level; Chrome not connected): coupon per-customer limit now counts account **or** phone; blocked phones get no checkout SMS; array-tampered fields give validation errors, not 500s (`App\Support\NormalizesStringInput`, guarded `x-form.*`); "already cancelled" message; checkout quote ignores stale responses (2026-10-07)
- Real-browser pass in Chrome (storefront order → admin confirm/cancel, bulk print, reviews, block): forms scroll to the first validation error; terms checkbox `required`; admin payment card says "Not collected"/"Amount refunded"; packing slips of cancelled/returned orders say DO NOT SHIP (2026-10-08)
- F-028 Admin dashboard: `Dashboard` module (read-only), sales today/month (booked = not cancelled/returned, Bangladesh days), pending orders, new customers, 7/30-day chart, orders by status, recent orders, low stock; numbers cached 5 min; **Milestone M4 reached** (2026-10-07)
- F-027 Reviews: `Review` module, `reviews` table, one review per customer per product (edit → pending again), verified purchase from delivered orders, admin approve/reject, product rating cache via `ProductService::updateRatingCache()`, product page breakdown + form, home testimonials from approved 4–5★ reviews (2026-10-07)
- F-026 Admin customers & messages: customer list/detail (orders, delivered spend, addresses), block/unblock (blocked accounts and their phone cannot check out), contact message inbox with New/Replied/Closed (2026-10-07)
- F-025b Printing: packing slips (2 per A4, COD amount or PAID, weight from live variant weights), order copy, bulk print from the list with optional Confirmed → Processing (2026-10-07)
- F-025 Admin orders: tabs/search/date range, transition map (`OrderStatus::allowedNextStatuses()`), `OrderStatusService` (shared with customer cancel), courier + tracking, internal note, mark COD paid, cancel/return restock + refund row, pending badge; fixed MariaDB `ON UPDATE` rewriting `orders.placed_at` / `otp_codes.expires_at` (2026-10-07)
- F-024 Customer orders & tracking: My Orders, timeline, cancel, Buy Again, guest tracking; **Milestone M3 reached** (2026-10-07)
- F-023 Checkout (COD): `Order` module, server-side totals, one transaction, guest phone code, `OrderPlaced` (2026-10-07)

## Open questions / blockers
- User to review Bangla spellings of the 50 Dhaka city thanas (`LocationSeeder::DHAKA_CITY_THANAS`)
- Confirm store defaults: footer values (Dhanmondi, support@nischoyota.com) used; prototype admin page had Banani / support@nischoyota.com
- Asset build needs Node 22 (`nvm use 22`)

## Dev login
Local admin (seeded by `migrate:fresh --seed`): `01700000000` / `password`. Run on port 8000 (`APP_URL`) so uploaded images load; `php artisan storage:link` once.

## Next
1. F-029 Notifications (blocked on G-6: choose the SMS provider)
2. Real-browser check by hand: admin order status flow + cancel modal, bulk print, block dialog, review stars; F-021–F-024 drawer/qty/hearts/checkout
3. G-3: fill in `prd.md` business rules (return window days are printed nowhere yet)
