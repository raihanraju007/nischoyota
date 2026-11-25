# F-011: Coupons

- **Status:** Done
- **Module:** `app/Modules/Promotion` (new, ADR-008)
- **Phase:** 1
- **Depends on:** F-002, ADR-007 (D4), ADR-012

## 1. Objective
The admin manages discount codes; checkout (F-023) asks `CouponService` whether a code applies and how much it takes off.

## 2. User stories
- As the **owner**, I want fixed (৳120 off) and percent (10% off, max ৳500) coupons with a minimum order, usage limits and dates.
- As a **customer** (F-021/F-023), I want a clear reason when a code does not work.

## 3. Business rules
1. Code: 3–30 characters, A–Z, 0–9 and dashes, stored UPPERCASE, unique. Customers may type it in any case.
2. Type (`CouponType`): **fixed** — value in taka (0.01–100,000), stored as paisa; **percent** — whole number 1–100. Max discount (taka) only for percent coupons.
3. Optional: minimum order (taka), total usage limit (≥ 1), usage limit per customer (≥ 1), start date, end date (end ≥ start; the coupon works until the end of that day, Asia/Dhaka).
4. Discount: fixed → min(value, subtotal); percent → floor(subtotal × value / 100), capped by max discount; never more than the subtotal. Money in paisa.
5. A code applies when, in this order: it exists and is active → it has started → it has not ended → the subtotal reaches the minimum → total uses < limit → the customer's uses < per-customer limit. Each failure has its own message (rule 7).
6. `used_count` is increased by checkout (F-023), which also passes the customer's own usage count.
7. Messages: "This coupon code is not valid." · "This coupon is not active yet." · "This coupon has expired." · "Spend at least ৳X to use this coupon." · "This coupon has been fully used." · "You have already used this coupon."
8. Status shown in the list (`CouponStatus`): Active, Scheduled, Expired, Used up, Inactive.
9. Coupons are **admin only** (`manage-store`, ADR-012). Deleting a coupon keeps past orders' discount (orders store a snapshot, D6).

## 4. Scope
- `coupons` table, model, enums, factory, seeder (prototype codes SAVE120, EID10, NEWUSER, FLASH20)
- `CouponService` (`apply()`, CRUD), `CouponDiscount` result, `CouponNotApplicableException`
- `/admin/coupons` list (code, discount, min order, used/limit, dates, status) + `/admin/coupons/create` and `/{coupon}/edit` pages; delete with confirmation

## 5. Out of scope
- Entering a code in cart/checkout (F-021, F-023), automatic promotions, free-shipping coupons

## 7. Database requirements
`coupons` as in [database.md §3.6](../../database.md#36-orders--payments).

## 8. Routes / API
`Route::resource('coupons')->except('show')` under `/admin`, `can:manage-store`.

## 11. Acceptance criteria
- [x] CRUD with validation (code format/unique, type-specific value, dates)
- [x] Discount calculation (fixed, percent, cap, never above subtotal) — unit tests
- [x] Every rule-5 failure with its message
- [x] Status labels
- [x] Admin only; staff 403

## 12. Multi-vendor impact
Phase 2 may add vendor-funded coupons (`coupons.vendor_id`); the validation stays in `CouponService`.

## 13. Open questions
- None (edit uses pages instead of the prototype's modal so validation errors show next to the fields).
