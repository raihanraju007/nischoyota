# F-002: Admin authentication & access

- **Status:** Done
- **Module:** `app/Modules/Account`
- **Phase:** 1
- **Depends on:** F-001, ADR-007 (D1, D8), ADR-009, ADR-012

## 1. Objective
Only admin and staff users can open `/admin`. They sign in with phone or email + password, sign out,
and change their own password. This also creates the `Account` module and moves `User` into it.

## 2. User stories
- As an **admin/staff**, I want to sign in with my phone or email so that I can manage the store.
- As an **admin/staff**, I want to change my password and sign out.
- As the **owner**, I want customers and blocked users kept out of the admin panel.
- As a **developer**, I want a command to create the first admin.

## 3. Business rules
1. A user has a role (`UserRole`: customer, staff, admin) and a status (`UserStatus`: active, blocked).
2. Login ID is a phone **or** an email (D1). Phones are normalised to `01XXXXXXXXX` (`+8801…`, `8801…`, spaces and dashes removed).
3. Only `admin` and `staff` with status `active` can sign in to the admin panel. Customers get "This account cannot access the admin panel."
4. Wrong login or password always shows the same message: "These credentials do not match our records."
5. 5 failed attempts per login ID + IP → locked for 60 seconds ("Too many attempts. Try again in N seconds.").
6. Successful login regenerates the session, sets `last_login_at`, and goes to the intended admin page or the dashboard.
7. Every `/admin/*` page except login needs an active admin/staff user. A guest goes to `/admin/login`; a customer gets 403. A user blocked while signed in is signed out on the next request.
8. Admin-only areas (ADR-012) use the `manage-store` gate. In this feature: **Coupons**. Staff get 403 and the sidebar hides those links. Settings, delivery zones and CMS pages get the gate in their own features.
9. Change password: needs the current password; the new one must be at least 8 characters and confirmed. Every admin user (admin and staff) can change their own password.
10. `php artisan app:create-admin` creates an active admin (name, phone, optional email, password), validating like the forms.

## 4. Scope
- `Account` module: provider, `User` model (moved from `app/Models`), enums, repository, services, requests, controllers, routes
- Migration: add `phone`, `phone_verified_at`, `role`, `status`, `last_login_at` to `users`; make `email` nullable
- Admin login / logout / change password; `admin` middleware; `manage-store` gate
- Admin layout: real user name + role in the top bar, logout as a POST form, sidebar hides admin-only links for staff
- All existing static `/admin` pages move behind `auth` + `admin`
- `ModuleServiceProvider`: `routes/admin.php` gets `auth` + `admin` middleware by default (secure by default)
- `UserFactory` states, `DatabaseSeeder` creates a local admin, `app:create-admin` command

## 5. Out of scope
- Customer login/register/OTP (F-014), staff account management UI (F-026), "forgot password" for admins (use the command or another admin for now)

## 6. UI requirements
- `admin/auth/login`: field becomes **"Phone or Email"** (`login`), keeps the prototype design; errors under the field via `x-form.input`; flash toast after logout
- `admin/settings`: the existing "Change Password" panel posts for real; success toast
- `layouts/admin`: user name/role from the signed-in user; Logout becomes a `<form method="POST">` styled like the current link

## 7. Database requirements
| Table | Columns (key ones) | Notes |
|---|---|---|
| users | `phone` string(11) unique, `phone_verified_at`, `role` string index (default customer), `status` string (default active), `last_login_at`; `email` → nullable | New migration; the default users migration is not edited |

## 8. Routes / API
| Method | URI | Name | Controller@action | Auth |
|---|---|---|---|---|
| GET | /admin/login | admin.login | Admin\LoginController@create | guest |
| POST | /admin/login | admin.login.store | Admin\LoginController@store | guest |
| POST | /admin/logout | admin.logout | Admin\LoginController@destroy | auth, admin |
| PUT | /admin/password | admin.password.update | Admin\PasswordController@update | auth, admin |

## 9. Security requirements
- Rate limit in the login FormRequest (rule 5); session regenerated on login, invalidated on logout; CSRF on all forms
- Passwords hashed (`hashed` cast); `role` and `status` are **not** mass-assignable from requests
- Authorization: `admin` middleware (rule 7), `manage-store` gate (rule 8)

## 10. Edge cases
- Login with `+880 1712-345678` matches a user stored as `01712345678`
- Email login is case-insensitive
- Already signed-in admin opening `/admin/login` → dashboard
- User with no email can still sign in by phone

## 11. Acceptance criteria
- [x] Guest opening any admin page is redirected to `/admin/login`
- [x] Customer → 403 on admin pages and cannot sign in to admin
- [x] Active admin and staff can sign in with phone or email and reach the dashboard
- [x] Blocked admin cannot sign in, and is signed out if blocked mid-session
- [x] Wrong credentials show the generic message; the 6th attempt within a minute is throttled
- [x] Logout ends the session
- [x] Staff get 403 on Coupons and don't see its link; admins do
- [x] Change password works and validates current password + confirmation
- [x] `app:create-admin` creates an active admin; invalid input is rejected
- [x] `migrate:fresh --seed` works and seeds a local admin

## 12. Multi-vendor impact
Vendors get their own tables in Phase 2 (D8); the admin middleware checks `UserRole` methods, so a later
vendor panel adds its own middleware instead of changing this one.

## 13. Open questions
- [x] Admin login field: "Phone or Email" — approved 2026-10-06
- [x] Local seed admin: phone `01700000000`, email `admin@nischoyota.test`, password `password` (local only) — approved 2026-10-06
