# F-025b: Print packing slip & order copy

- **Status:** Done
- **Module:** `app/Modules/Order`
- **Phase:** 1
- **Depends on:** F-025, F-010

## 1. Objective
Staff print a courier label for the parcel and an order copy (invoice) for inside the box, straight from the browser ("Print / Save as PDF"), one order or many at once.

## 2. User stories
- As **staff**, I want to print the packing slips of all confirmed orders before the courier picks up, and have those orders move to Processing.
- As **staff**, I want to print an order copy for the customer.

## 3. Business rules
1. **Packing slip** (half A4, two per page): sender (store name, phone, address from settings), receiver (name, phone, full address division → district → upazila), order number in large text, **COD to collect** (order total, "PAID" when paid, or "DO NOT SHIP" for a cancelled / returned order), item count, weight (sum of the variants' current `weight_grams` × qty; "—" when no item has a weight), courier + tracking code, delivery zone.
2. **Order copy** (A4): store details, order number and date, customer + shipping address, items (name, variant, SKU, qty, unit price, line total), subtotal, discount (+ coupon), delivery charge, total, payment method/status, a return policy line with the store's return-policy URL, thank-you note.
3. **Bulk print** from the order list: select orders with checkboxes → "Print packing slips". Max 100 per print. Optional "Move confirmed orders to Processing": each selected **Confirmed** order moves to Processing with the history note "Packing slip printed"; others are printed only.
4. Print pages have no admin chrome, a "Print" button (hidden on paper) and open in a new tab.

## 4. Scope
- `OrderPrintService`, `Admin\OrderPrintController`, `PrintPackingSlipsRequest`
- `ProductVariantService::weightsInGrams()` (Catalog, read-only)
- Views: `components/layouts/print`, `admin/orders/print/invoice`, `admin/orders/print/packing-slips`; print buttons on the detail page; checkboxes + bulk bar on the list
- Print CSS section in `admin.css`

## 5. Out of scope
- PDF generation package, barcodes/QR codes, courier label formats (Pathao/Steadfast APIs), VAT invoice numbering

## 8. Routes
| Method | URI | Name |
|---|---|---|
| GET | `/admin/orders/{order}/invoice` | `admin.orders.invoice` |
| GET | `/admin/orders/{order}/packing-slip` | `admin.orders.packing-slip` |
| POST | `/admin/orders/packing-slips` | `admin.orders.packing-slips` |

## 9. Security
Staff + admin (ADR-012). Bulk ids validated (`exists`, max 100). Printing is read-only except the opt-in status move, which goes through `OrderStatusService` (transition rules + history).

## 10. Edge cases
- No order selected → back with an error
- Selected order is pending/shipped with "move" ticked → printed, not moved
- Item whose product was deleted → printed from the snapshot, weight unknown

## 11. Acceptance criteria
- [x] Single slip and order copy pages with store details
- [x] Bulk slips, two per A4 page
- [x] Optional move Confirmed → Processing with history
- [x] Paid orders show "PAID" instead of the COD amount

## 12. Multi-vendor impact
None; per-vendor slips would print per sub-order later.

## 13. Open questions (decided — user: "all approved", 2026-10-07)
- [x] Weight from live variant weights (no snapshot column) — good enough for courier booking.
- [x] Return policy line links to `/return-policy` without a day count (G-3 business rules still open).
