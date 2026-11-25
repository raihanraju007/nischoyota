# F-011: Coupons — Tasks

## Database
- [x] Migration `coupons`; `Coupon` model; `CouponType`, `CouponStatus` enums; factory; `CouponSeeder`

## Backend
- [x] `PromotionServiceProvider`; `CouponRepository`; `CouponService` (CRUD, `apply()`); `CouponDiscount`; `CouponNotApplicableException`
- [x] `SaveCouponRequest`; `Admin\CouponController`; routes (`can:manage-store`); remove static route

## Frontend
- [x] Coupons list, create/edit page, delete modal

## Tests
- [x] Unit: discount maths; Feature: apply rules + messages, CRUD, validation, status, authorization

## Verification / Documentation
- [x] Promotion tests + layout test; pint; migrate; build; HTTP; docs
