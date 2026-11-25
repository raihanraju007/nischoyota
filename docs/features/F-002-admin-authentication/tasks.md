# F-002: Admin authentication & access — Tasks

Status: `[ ]` todo · `[~]` doing · `[x]` done. Tick items as they finish. Commit after each green group.

## Database
- [x] Migration: phone, phone_verified_at, role, status, last_login_at; email nullable
- [x] Move `User` to `App\Modules\Account\Models\User` (casts for enums, `$fillable` without role/status); update `config/auth.php`, factory
- [x] `UserRole`, `UserStatus` enums (labels, `canAccessAdminPanel()`, `canManageStore()`)
- [x] `UserFactory` states: admin, staff, customer, blocked, withoutEmail
- [x] `DatabaseSeeder`: local admin

## Backend
- [x] `AccountServiceProvider` (routes, gate `manage-store`) + `bootstrap/providers.php`
- [x] `ModuleServiceProvider`: `auth` + `admin` middleware on `routes/admin.php`
- [x] `PhoneNumber` normaliser
- [x] `UserRepository` (findByLogin, updateLastLogin, updatePassword)
- [x] `AdminAuthenticationService` (attempt login → domain exceptions), `PasswordService`
- [x] `AdminLoginRequest` (rate limit), `UpdatePasswordRequest`
- [x] `EnsureUserCanAccessAdmin` middleware (alias `admin`), guest/auth redirects in `bootstrap/app.php`
- [x] `Admin\LoginController`, `Admin\PasswordController`, module routes
- [x] Root `routes/web.php`: static admin pages behind `auth` + `admin`; coupons behind `can:manage-store`
- [x] `app:create-admin` command

## Frontend
- [x] Admin login form posts for real ("Phone or Email")
- [x] Admin layout: signed-in user, POST logout, hide admin-only links for staff
- [x] Settings → Change Password posts for real

## Tests
- [x] Unit: phone normaliser, enum rules
- [x] Feature: login (phone, email, wrong, customer, blocked, throttle, intended redirect), logout
- [x] Feature: admin access (guest, customer, staff, admin, blocked mid-session, manage-store gate)
- [x] Feature: change password
- [x] Feature: create-admin command
- [x] Update `AdminPagesTest` to sign in

## Verification
- [x] Narrow tests pass, then full suite
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `php artisan migrate:fresh --seed`
- [x] Manual browser check of login / logout / password

## Documentation
- [x] `feature.md` status → Done
- [x] Roadmap, `memory.md`, `architecture.md` (Account → Built), `database.md` (users)
