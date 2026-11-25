# F-028: Admin dashboard & reports

- **Status:** Done
- **Module:** `app/Modules/Dashboard` (new, planned in ADR-008: read-only, calls other modules' services)
- **Phase:** 1
- **Depends on:** F-025

## 1. Objective
Staff open the admin panel and see at a glance how the store is doing today and this month, what needs attention, and which products are running out.

## 2. User stories
- As the **owner**, I want today's and this month's sales and orders, new customers and a sales chart.
- As **staff**, I want the newest orders, orders per status and the low-stock list on one page.

## 3. Business rules
1. **Sales** = sum of `orders.total` placed in the period, excluding `cancelled` and `returned` orders. Days and months are Bangladesh days (`config('shop.timezone')`).
2. Stat cards: Sales today (with order count), Sales this month (with order count), Orders waiting (pending), New customers this month (role customer).
3. Sales chart: last 7 days (default) or last 30 days (`?period=30`), one bar per day including days without sales; tooltip shows sales and order count.
4. Orders by status: all-time count per status, each linking to the filtered order list.
5. Recent orders: the 6 newest, linking to the admin order page.
6. Low stock: active variants at or below the low-stock threshold (setting), out-of-stock first, 6 shown, with the total count and a link to the Inventory "Low stock" tab.
7. **Caching:** sales numbers, chart, status counts and new customers are cached for 5 minutes per period and day; recent orders and low stock are always live.

## 4. Scope
- Order: `OrderReportService` + repository queries (sales between dates, sales per day, count per status, recent)
- Account: `CustomerService::countRegisteredSince()`
- Inventory: `InventoryService::lowStockVariants()` / `lowStockCount()`
- Dashboard: `ChartPeriod`, `DashboardStats`, `DashboardService`, `Admin\DashboardController`, `ShowDashboardRequest`, routes; static dashboard route removed
- Views: `admin/dashboard`, `x-admin.sales-chart` (paisa), `x-admin.stat-card`

## 5. Out of scope
- Exports, profit/cost reports, per-product sales reports, date pickers

## 8. Routes
| Method | URI | Name |
|---|---|---|
| GET | `/admin` | `admin.dashboard` (staff + admin) |

## 11. Acceptance criteria
- [x] Today / month sales and counts, pending orders, new customers
- [x] 7 / 30 day chart with empty days
- [x] Orders by status, recent orders, low stock
- [x] Aggregates cached 5 minutes
- **Milestone M4 reached** when done

## 12. Multi-vendor impact
None; per-vendor reports would filter by vendor later.

## 13. Open questions (decided — user: "all approved", 2026-10-07)
- [x] Sales exclude cancelled and returned orders but include not-yet-delivered ones (booked sales).
- [x] No trend percentages for now (the stat card still supports them).
