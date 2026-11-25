# F-009: Locations & delivery zones — Tasks

## Database
- [x] Migrations; models; factories; dataset in `database/data/bangladesh/`; `LocationSeeder`, `DeliveryZoneSeeder`

## Backend
- [x] `LocationServiceProvider`; `LocationRepository`, `DeliveryZoneRepository`; `LocationService`, `DeliveryChargeService` (+ `DeliveryQuote`)
- [x] Requests; `Admin\DeliveryZoneController`, `Admin\DeliveryAreaController`, `LocationController` (JSON); routes; `config/shop.php`

## Frontend
- [x] Zones page, areas page, district page; settings panel; sidebar link

## Tests
- [x] Unit/feature: quote rules; zone CRUD + guards; areas; JSON endpoints; authorization (staff 403)

## Verification / Documentation
- [x] Location tests + Settings/Account tests touched by the layout; pint; migrate:fresh --seed; build; HTTP; docs
