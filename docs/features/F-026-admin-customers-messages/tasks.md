# F-026: Admin customers & messages — Tasks

Status: `[ ]` todo · `[~]` doing · `[x]` done.

## Backend
- [x] Customer list/detail queries, `CustomerService`, block/unblock
- [x] Order stats per customer (`CustomerOrderService::statsFor()`)
- [x] Checkout refuses blocked accounts / phones
- [x] Contact message inbox queries + status change
- [x] Controllers, requests, routes; static customers route removed
- [x] Sidebar Messages entry + new-message badge

## Frontend
- [x] `admin/customers/index`, `admin/customers/show`
- [x] `admin/messages/index`, `admin/messages/show`

## Tests
- [x] Customers: list, search, filter, stats, detail, 404 for staff ids, block/unblock, blocked cannot order
- [x] Messages: tabs, search, detail, status change, badge, customer 403

## Verification
- [x] Narrow tests pass
- [x] `vendor/bin/pint --dirty --format agent`

## Documentation
- [x] Status, roadmap, memory, architecture, index
