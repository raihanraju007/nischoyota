# F-026: Admin customers & messages

- **Status:** Done
- **Module:** `app/Modules/Account` (customers), `app/Modules/Content` (messages)
- **Phase:** 1
- **Depends on:** F-024, F-020

## 1. Objective
Staff look up customers with their orders and addresses, block fake-order abusers, and work through contact form messages.

## 2. User stories
- As **staff**, I want to find a customer by name, phone or email and see their orders, spend and addresses.
- As **staff**, I want to block a customer who places fake orders, and unblock them later.
- As **staff**, I want an inbox of contact messages that I can mark replied or closed.

## 3. Business rules
### Customers
1. `/admin/customers` (staff + admin) lists users with role `customer`, newest first, 20 per page; search name / phone / email; status filter All / Active / Blocked (with counts).
2. Columns: name + email, phone, orders (all statuses), total spent (sum of **delivered** orders' totals), joined, status.
3. Detail `/admin/customers/{id}`: profile (phone, email, verified, joined, last login), stats (orders, delivered spend, cancelled count), saved addresses, orders (10 per page, linking to the admin order page). Non-customer ids → 404.
4. **Block / unblock** (POST, with a confirm dialog). A blocked customer:
   - cannot sign in, and is signed out on the next account page (existing `active` middleware);
   - **cannot place orders**: checkout is refused when the signed-in account is blocked, or when the order phone belongs to a blocked account (guest checkout with the same phone). Message: "We can't take orders from this account. Please contact customer support."
### Messages
5. `/admin/messages` inbox: tabs New / Replied / Closed / All with counts, search name / phone / email, newest first, 20 per page; each row shows sender, subject, the first 120 characters and the date.
6. Detail `/admin/messages/{id}`: full message, sender details with Call / Email links, link to the customer when signed in; buttons to mark **Replied**, **Closed** or back to **New**.
7. Sidebar: "Messages" under Sales with the count of **new** messages (hidden at 0).

## 4. Scope
- Account: `CustomerFilter`, `CustomerService`, `UserRepository` customer queries, `Admin\CustomerController`, `ListCustomersRequest`, routes
- Order: `CustomerOrderService::statsFor()` / `OrderRepository::statsForCustomers()`, `CustomerOrderStats`; blocked check in `OrderService::place()` via `CustomerService::isBlocked()`
- Content: `ContactMessageService` / repository inbox queries, `Admin\ContactMessageController`, `ListContactMessagesRequest`, `UpdateContactMessageStatusRequest`, routes
- Views: `admin/customers/index`, `admin/customers/show`, `admin/messages/index`, `admin/messages/show`; sidebar entry + badge

## 5. Out of scope
- Staff user management (create/remove staff accounts — `app:create-admin` exists), replying by email/SMS from the panel, editing customer profiles, deleting customers

## 8. Routes
| Method | URI | Name |
|---|---|---|
| GET | `/admin/customers` | `admin.customers.index` |
| GET | `/admin/customers/{customer}` | `admin.customers.show` |
| POST | `/admin/customers/{customer}/block` | `admin.customers.block` |
| POST | `/admin/customers/{customer}/unblock` | `admin.customers.unblock` |
| GET | `/admin/messages` | `admin.messages.index` |
| GET | `/admin/messages/{message}` | `admin.messages.show` |
| PATCH | `/admin/messages/{message}/status` | `admin.messages.status` |

## 9. Security
- Staff + admin (ADR-012). Only `customer` accounts can be opened/blocked here: staff/admin ids → 404, so staff cannot lock out an admin.

## 10. Edge cases
- Block an already blocked customer → no change, info message
- Guest checkout with the phone of a blocked account → refused
- Message from a since-deleted user → shown without the customer link

## 11. Acceptance criteria
- [x] Customer list with search, status filter, orders and spend
- [x] Customer detail with orders, addresses and stats
- [x] Block/unblock; blocked accounts cannot sign in or order
- [x] Messages inbox with tabs, search, detail and status changes
- [x] New-message badge

## 12. Multi-vendor impact
None.

## 13. Open questions (decided — user: "all approved", 2026-10-07)
- [x] "Total spent" counts delivered orders only.
- [x] Blocking also stops checkout by the same phone as a guest.
- [x] Staff management stays on `app:create-admin` for Phase 1.
