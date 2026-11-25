# ADR-009: Authentication built on Laravel core, no auth package

- **Status:** Accepted
- **Date:** 2026-10-06

## Context
Login is by phone **or** email (D1) with SMS OTP (D2). The login, register and reset views already exist.

## Options
1. **Laravel core** (`Auth`, session guard, `RateLimiter`, `Password` broker): full control, no new dependency.
2. **Laravel Fortify**: ready-made actions, but phone login and OTP need custom pipelines anyway.
3. **Breeze/Jetstream**: scaffolds its own views, which would replace the approved design.

## Decision
Option 1. One `web` session guard for customers and admin users; the admin area is separated by role middleware.

## Consequences
- We write and test the auth controllers, services and rate limits ourselves (F-002, F-014).
- No Composer package is added.
