# F-010: Store settings — Tasks

## Database
- [x] Migration `settings`; `Setting` model; `StoreSetting` enum (keys, defaults)

## Backend
- [x] `ContentServiceProvider`; `SettingRepository`; `SettingService` (cached `current()`, `save()`); `StoreSettings` DTO
- [x] `UpdateStoreSettingsRequest`; `Admin\SettingsController`; routes; remove static route
- [x] `DeliveryChargeService` and low-stock call sites use settings

## Frontend
- [x] Settings page: store info + shop rules (admin), payment info, change password

## Tests
- [x] Settings: show/defaults, save, validation, staff, cache; free delivery + low stock via settings; related Location/Inventory/Catalog tests

## Verification / Documentation
- [x] Related tests, pint, migrate, build, HTTP; docs
