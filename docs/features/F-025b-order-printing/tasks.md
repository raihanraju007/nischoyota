# F-025b: Print packing slip & order copy — Tasks

Status: `[ ]` todo · `[~]` doing · `[x]` done.

## Backend
- [x] `ProductVariantService::weightsInGrams()`
- [x] `OrderPrintService` (load for print, weights, move printed confirmed orders)
- [x] `Admin\OrderPrintController`, `PrintPackingSlipsRequest`, routes

## Frontend
- [x] Print layout + print CSS (A4, half-A4 slips, no chrome)
- [x] Packing slips page, order copy page
- [x] Print buttons on the detail page; checkboxes + bulk bar on the list

## Tests
- [x] Slip and invoice content (COD amount / PAID, weight, store details)
- [x] Bulk print, validation, optional move to Processing (confirmed only), customers 403

## Verification
- [x] Narrow tests pass
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `npm run build`

## Documentation
- [x] Status, roadmap, index
