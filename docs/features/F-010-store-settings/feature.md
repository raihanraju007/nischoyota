# F-010: Store settings

- **Status:** Done
- **Module:** `app/Modules/Content` (new, ADR-008)
- **Phase:** 1
- **Depends on:** F-002, F-008, F-009, ADR-011, ADR-012

## 1. Objective
The admin edits store details and shop rules on the Settings page; the rest of the app reads them from one cached place.

## 2. User stories
- As the **owner**, I want to change the store name, support phone/email, address and support hours shown to customers.
- As the **owner**, I want to set the free-delivery threshold and when stock counts as "low".

## 3. Business rules
1. Settings (`StoreSetting` enum keys, value stored as JSON):
   | Key | Rule | Default |
   |---|---|---|
   | store_name | required, ≤ 100 | Nischoyota |
   | support_phone | required, ≤ 20, digits/spaces/+/- | +880 9612-345678 |
   | support_email | required, email | support@nischoyota.com |
   | store_address | nullable, ≤ 255 | House 12, Road 5, Dhanmondi, Dhaka 1205, Bangladesh |
   | support_hours | nullable, ≤ 100 | Everyday, 9:00 AM – 10:00 PM |
   | free_delivery_threshold | taka 0–1,000,000, empty = no free delivery; stored as paisa | ৳2,000 (`config/shop.php`) |
   | low_stock_threshold | whole number 0–1,000 | 5 |
2. A key never saved falls back to its default. All settings are cached; saving clears the cache.
3. Only admins see and save the store panels (`manage-store`); staff see the Settings page with **Change Password** only.
4. `DeliveryChargeService` uses `free_delivery_threshold` (null → never free). Inventory, product list and variants page use `low_stock_threshold` instead of the constant.
5. Payment Methods panel: Cash on Delivery is the only Phase 1 method; bKash/Nagad are shown as "Coming after launch" (no demo toggles).

## 4. Scope
- `settings` table, `Setting` model, `StoreSetting` enum, `SettingRepository`, `SettingService` (+ `StoreSettings` DTO), `SettingsController` (show/update), routes
- Settings page made dynamic; replaces the static `Route::view('/settings')`
- Low-stock threshold wired into Catalog/Inventory; free-delivery threshold into Location

## 5. Out of scope
- Showing settings in the storefront header/footer/contact page (F-013, F-020), payment gateways

## 7. Database requirements
| Table | Columns |
|---|---|
| settings | `key` unique, `value` json, timestamps |

## 8. Routes / API
| Method | URI | Name | Auth |
|---|---|---|---|
| GET | /admin/settings | admin.settings | admin, staff |
| PUT | /admin/settings | admin.settings.update | admin (`manage-store`) |

## 11. Acceptance criteria
- [x] Settings page shows saved values or defaults; admin saves them (validation per rule 1)
- [x] Staff see only Change Password and get 403 on save
- [x] Cache cleared on save
- [x] Free delivery uses the saved threshold; empty = never free
- [x] Low stock tab/labels use the saved threshold

## 12. Multi-vendor impact
Store settings stay platform-wide; vendor settings would be a separate table in Phase 2.

## 13. Open questions
- [ ] The prototype admin page showed a different address/email (Banani, support@nischoyota.com) than the storefront footer (Dhanmondi, support@nischoyota.com). Defaults use the **footer** values — confirm or change them in the Settings page.
