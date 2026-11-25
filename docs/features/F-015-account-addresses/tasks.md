# F-015: Customer account & addresses — Tasks

## Database
- [x] Migration `addresses`; `Address` model; `AddressLabel`; factory; `OtpPurpose::PhoneChange`

## Backend
- [x] `EnsureAccountIsActive` middleware; `AddressPolicy`
- [x] `AddressRepository`, `AddressService`; `ProfileService`; `LocationService::allDivisions()`
- [x] Requests; `AccountDashboardController`, `ProfileController`, `PhoneChangeController`, `AddressController`; routes; static account routes behind auth

## Frontend
- [x] Account layout (user, logout); dashboard; profile + phone; addresses list + form; `<x-address-fields>` + JS

## Tests
- [x] Access, profile, password, phone change, addresses (CRUD, rules, ownership), dropdown options

## Verification / Documentation
- [x] Related tests; pint; migrate; build; HTTP; docs
