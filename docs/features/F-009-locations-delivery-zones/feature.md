# F-009: Locations & delivery zones

- **Status:** Done
- **Module:** `app/Modules/Location` (new, ADR-008)
- **Phase:** 1
- **Depends on:** F-002, ADR-007 (D7), ADR-011, ADR-012

## 1. Objective
Seed Bangladesh's divisions, districts and upazilas/thanas (English + Bangla) for address dropdowns, and work out
the delivery charge and delivery time for any upazila from its delivery zone.

## 2. User stories
- As a **customer** (F-015, F-023), I want Division → District → Upazila/Thana dropdowns and to see my delivery charge.
- As an **admin**, I want to change zone charges and delivery times, and choose which zone each district or thana belongs to.

## 3. Business rules
1. Data: 8 divisions, 64 districts, 494 upazilas from `nuhil/bangladesh-geocode` (MIT, copied into `database/data/bangladesh/` with attribution) **plus the Dhaka Metropolitan thanas** (Dhanmondi, Gulshan, Mirpur…), which official upazila lists do not contain. Official English spellings: Chattogram, Barishal, Cumilla, Cox's Bazar.
2. Zones (from the prototype): **Inside Dhaka** ৳60, 1–2 days · **Dhaka Suburbs** ৳100, 2–3 days · **Outside Dhaka** ৳120, 3–5 days.
3. Default mapping: every district → Outside Dhaka; Dhaka, Gazipur, Narayanganj districts → Dhaka Suburbs; every Dhaka city thana → Inside Dhaka (upazila override).
4. Zone of an upazila = its own zone if set, otherwise its district's zone.
5. Delivery quote for an upazila and order subtotal: zone, charge, min/max days. **Free delivery** when subtotal ≥ the free-delivery threshold (default ৳2,000 in `config/shop.php`; becomes a store setting in F-010).
6. An inactive zone means "We don't deliver to this area yet" (no quote).
7. Zone charge 0–10,000 taka, days 1–30 with min ≤ max, name unique. At least one zone must stay active. A zone used by districts/upazilas cannot be deleted.
8. Managing zones and areas is **admin only** (`manage-store`, ADR-012).
9. Public JSON endpoints for dependent dropdowns (rate-limited): districts of a division, upazilas of a district.

## 4. Scope
- `Location` module: tables, models, factories, seeders, `DeliveryChargeService`, admin controllers, JSON endpoints
- 🎨 `/admin/delivery-zones`: zones table + add/edit form (same layout as Brands)
- 🎨 `/admin/delivery-areas`: districts grouped by division, each with a zone select, saved together; per district `/admin/delivery-areas/{district}` lists its upazilas with "Same as district" or a zone override
- Settings page: Delivery Zones panel shows real zones with a "Manage" link (admin only); sidebar "Delivery" link (admin only)
- `config/shop.php` with `free_delivery_threshold`

## 5. Out of scope
- Address book (F-015), checkout (F-023), courier APIs, weight-based charges

## 7. Database requirements
| Table | Columns |
|---|---|
| delivery_zones | `name` unique, `charge` (paisa), `min_days`, `max_days`, `is_active`, `sort_order` |
| divisions | `name`, `name_bn` (no timestamps) |
| districts | `division_id` FK, `name`, `name_bn`, `delivery_zone_id` FK restrict |
| upazilas | `district_id` FK, `name`, `name_bn`, `delivery_zone_id` nullable FK restrict (override) |

## 8. Routes / API
| Method | URI | Name | Auth |
|---|---|---|---|
| GET | /locations/divisions/{division}/districts | locations.districts | public, throttled |
| GET | /locations/districts/{district}/upazilas | locations.upazilas | public, throttled |
| GET/POST, GET/PUT/DELETE | /admin/delivery-zones… | admin.delivery-zones.* | admin |
| GET/PUT | /admin/delivery-areas | admin.delivery-areas.index / .update | admin |
| GET/PUT | /admin/delivery-areas/{district} | admin.delivery-areas.show / .update-district | admin |

## 10. Edge cases
- Savar (Dhaka district, no override) → Dhaka Suburbs; Dhanmondi → Inside Dhaka; Cumilla Sadar → Outside Dhaka
- Subtotal exactly ৳2,000 → free
- Deactivating the last active zone → error

## 11. Acceptance criteria
- [x] Seeder: 8 / 64 / 494 + Dhaka thanas, Bangla names, default zones
- [x] Quote: zone resolution, free delivery threshold, inactive zone
- [x] Zone CRUD with validation and guards (admin only; staff 403)
- [x] Area mapping pages save district zones and upazila overrides
- [x] JSON endpoints return id/name/name_bn, ordered by name
- [x] Settings panel shows real zones

## 12. Multi-vendor impact
Phase 2 may charge delivery per vendor shipment; `DeliveryChargeService` is the single place to extend.

## 13. Open questions
- [ ] **Please review** the Bangla spellings of the Dhaka city thanas (written by hand; the official dataset has no city thanas).
