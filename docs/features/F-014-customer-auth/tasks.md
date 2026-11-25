# F-014: Customer authentication — Tasks

## Database
- [x] Migration `otp_codes`; `OtpCode` model; `OtpPurpose`, `OtpChannel`; factory

## Backend
- [x] `SmsGateway` + log/array drivers; `config/services.php` sms driver; binding
- [x] `OtpService` (send with rate limit, verify with attempts/expiry); `OtpCodeMail` + view
- [x] `ThrottlesLogins` trait shared with `AdminLoginRequest`
- [x] `CustomerAuthService` (register, login, verify phone, forgot, reset); exceptions
- [x] Requests; `LoginController`, `RegisterController`, `PhoneVerificationController`, `PasswordResetController`; routes; remove static routes

## Frontend
- [x] Login, register, verify phone, forgot, reset views (real forms, errors, resend)

## Tests
- [x] Feature per flow + OTP rules; admin login still green

## Verification / Documentation
- [x] Related tests; pint; migrate; build; HTTP; docs (database.md otp_codes)
