# F-014: Customer authentication

- **Status:** Done
- **Module:** `app/Modules/Account`
- **Phase:** 1
- **Depends on:** F-002, F-013, ADR-007 (D1, D2), ADR-009

## 1. Objective
Customers register with their phone (verified by an SMS code), log in with phone or email, and reset a forgotten
password with a code sent by SMS or email.

## 2. User stories
- As a **customer**, I want to create an account with my phone number and confirm it with a code.
- As a **customer**, I want to log in with my phone or email, and log out.
- As a **customer**, I want to reset my password with a code if I forget it.

## 3. Business rules
1. **Register:** name (≤ 100), phone (BD mobile, normalised `01XXXXXXXXX`), email (optional, unique), password (≥ 8, confirmed), Terms accepted. The account is created as an unverified customer; a code is sent by SMS; the customer enters it on **Verify phone**; then they are logged in.
2. A phone that belongs to an **unverified** account can register again (name/email/password are replaced, a new code is sent). A **verified** phone is "already registered".
3. **Login:** phone or email + password, "remember me". Same message for unknown user / wrong password. 5 failed tries per login + IP → 60 s lock (same as admin). Blocked users get "This account is blocked…". An unverified phone gets a new code and goes to Verify phone instead of being logged in. Success → intended page or My Account.
4. **Logout:** POST, session invalidated.
5. **Forgot password:** phone or email → if an account exists, a code is sent by SMS (phone) or email; the page always says "If an account exists, we've sent a code" (no account enumeration). **Reset:** code + new password (≥ 8, confirmed) → password changed, phone counted as verified if the code came by SMS, user logged in.
6. **Codes (OTP):** 6 digits, stored **hashed**, valid **5 minutes**, **5 wrong tries** then the code is dead; a new code cancels older ones. Sending is limited to **1 per 60 s and 5 per hour** per recipient. "Resend code" on the code pages.
7. **SMS sender** behind an `SmsGateway` interface; driver from `config('services.sms.driver')`: `log` (writes the SMS to the log, default until a provider is chosen — G-6) and `array` (tests). Email codes use a Mailable.
8. Signed-in users opening login/register go to the home page (admins to the admin dashboard).

## 4. Scope
- `otp_codes` table (`recipient` + `channel` instead of `phone`, so email codes fit), `OtpCode` model, `OtpPurpose`, `OtpChannel` enums
- `OtpService`, `SmsGateway` (+ `LogSmsGateway`, `ArraySmsGateway`), `OtpCodeMail`
- `CustomerAuthService`, requests (shared login throttling with the admin login), controllers, routes
- Storefront views: login, register, verify phone (new, same card design), forgot password, reset password

## 5. Out of scope
- Account pages and address book (F-015), changing phone (F-015), guest order OTP (F-023), real SMS provider (G-6)

## 7. Database requirements
| Table | Columns |
|---|---|
| otp_codes | `recipient` (phone or email), `channel` (sms/email), `purpose`, `code_hash`, `attempts`, `expires_at`, `verified_at`, `ip_address`, timestamps; index (`recipient`, `purpose`, `created_at`) |

## 8. Routes / API
| Method | URI | Name | Auth |
|---|---|---|---|
| GET/POST | /login | login / login.store | guest |
| POST | /logout | logout | auth |
| GET/POST | /register | register / register.store | guest |
| GET/POST | /verify-phone | phone.verify / phone.verify.store | guest (pending verification in session) |
| POST | /verify-phone/resend | phone.verify.resend | guest |
| GET/POST | /forgot-password | password.request / password.email | guest |
| GET/POST | /reset-password | password.reset / password.store | guest (pending reset in session) |
| POST | /reset-password/resend | password.resend | guest |

## 9. Security requirements
- Hashed codes; attempt and send limits; session regenerated on login; no enumeration on forgot password; `role`/`status` never mass-assigned

## 11. Acceptance criteria
- [x] Register → code → verify → logged in; duplicate verified phone rejected; unverified phone re-registers
- [x] Login by phone/email; wrong credentials; throttle; blocked; unverified → verify
- [x] Logout
- [x] Forgot/reset by phone (SMS) and by email (mail); unknown account gets the same message
- [x] Code expiry, 5 wrong tries, new code cancels old, send rate limits, resend

## 12. Multi-vendor impact
Vendors (Phase 2) get their own login; this flow stays for customers.

## 13. Open questions
- [ ] G-6: SMS provider — `log` driver until chosen.
- [x] Email reset uses a code (as in the prototype) instead of Laravel's reset link → `otp_codes.recipient/channel` instead of `phone` (decided 2026-10-06; database.md updated).
- [x] Minimum password length 8 (prototype said 6) to match admins.
