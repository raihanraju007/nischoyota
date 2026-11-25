# F-015: Customer account & addresses

- **Status:** Done
- **Module:** `app/Modules/Account` (uses `Location` services for the dropdowns)
- **Phase:** 1
- **Depends on:** F-009, F-014

## 1. Objective
Signed-in customers manage their profile, password, phone and an address book that checkout (F-023) will use.

## 2. User stories
- As a **customer**, I want to update my name and email, change my password, and change my phone with a code.
- As a **customer**, I want to save Home/Office addresses with Division → District → Upazila and pick a default.

## 3. Business rules
1. All `/account/*` pages need a signed-in, **active** user; a user blocked mid-session is logged out.
2. **Profile:** name (≤ 100), email (optional, unique). Phone is shown read-only with a "Change" link.
3. **Change phone:** new BD mobile (not used by another verified account) → code sent by SMS to the **new** number (`OtpPurpose::PhoneChange`) → correct code updates the phone (verified). Same code rules as F-014.
4. **Change password:** current password + new (≥ 8, confirmed, different).
5. **Addresses:** label (`AddressLabel`: home, office, other), recipient name (≤ 100), phone (BD mobile), division, district (must belong to the division), upazila/thana (must belong to the district), area (optional, ≤ 100), address line (≤ 255), postal code (optional, 4 digits). Max **10** addresses.
6. The first address is the default automatically. "Set default" moves it; deleting the default makes the oldest remaining address the default.
7. A customer can only see/edit/delete their own addresses (policy) — others get 404.
8. Dashboard: welcome, default address, quick links. Order statistics and recent orders appear in F-024 (no fake numbers before then).
9. Sidebar shows the real name and email (or phone), and Logout posts the logout form.

## 4. Scope
- `addresses` table, `Address` model (relations to Location models for display), `AddressLabel` enum, factory, policy
- `AddressService`, `ProfileService` (profile, password, phone change), requests, controllers, routes
- `<x-address-fields>` component (dependent selects; works without JS) + small JS using the F-009 JSON endpoints
- Views: dashboard, profile (+ change phone), addresses list, address create/edit page; account layout with real user + logout
- Existing static order pages move behind login (made dynamic in F-024)

## 5. Out of scope
- Orders (F-024), wishlist (F-022), profile photo (not in the data model — button removed), account deletion

## 7. Database requirements
`addresses` as in database.md §3.2.

## 8. Routes / API
| Method | URI | Name |
|---|---|---|
| GET | /account | account.dashboard |
| GET/PUT | /account/profile | account.profile / account.profile.update |
| PUT | /account/password | account.password.update |
| GET/POST | /account/phone | account.phone / account.phone.send-code |
| POST | /account/phone/verify | account.phone.verify |
| GET | /account/addresses | account.addresses |
| GET/POST | /account/addresses/create, /account/addresses | account.addresses.create / .store |
| GET/PUT/DELETE | /account/addresses/{address}(/edit) | account.addresses.edit / .update / .destroy |
| POST | /account/addresses/{address}/default | account.addresses.default |

## 11. Acceptance criteria
- [x] Guests sent to login; blocked users logged out
- [x] Profile and password update with validation
- [x] Phone change with code to the new number
- [x] Address CRUD, location consistency, 10-address limit, default rules, ownership (404)
- [x] Dropdowns filled server-side for the selected division/district (no-JS) and by JS on change

## 12. Multi-vendor impact
None.

## 13. Open questions
- None (add/edit address as pages instead of the prototype modal, like coupons; profile photo removed).
