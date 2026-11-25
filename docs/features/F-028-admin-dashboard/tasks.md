# F-028: Admin dashboard & reports — Tasks

Status: `[ ]` todo · `[~]` doing · `[x]` done.

## Backend
- [x] `OrderReportService` + `OrderRepository` report queries
- [x] `CustomerService::countRegisteredSince()`, `InventoryService::lowStockVariants()/lowStockCount()`
- [x] `Dashboard` module: provider, `ChartPeriod`, `DashboardStats`, `DashboardService` (cached), controller, request, route; static route removed

## Frontend
- [x] `admin/dashboard` from real data; `x-admin.sales-chart` takes paisa

## Tests
- [x] Sales today/month exclude cancelled/returned, Bangladesh day boundaries
- [x] Chart 7/30 days with empty days, status counts, recent orders, low stock, new customers
- [x] Cache, staff access

## Verification
- [x] Narrow tests pass
- [x] `vendor/bin/pint --dirty --format agent`

## Documentation
- [x] Status, roadmap (Milestone M4), memory, architecture, index
