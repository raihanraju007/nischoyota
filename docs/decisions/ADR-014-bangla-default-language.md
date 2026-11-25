# ADR-014: Bangla default language, and clothing paused

- **Status:** Accepted
- **Date:** 2026-11-25

## Context
The store sells to Bangladeshi families. The UI was English-only. The owner wants Bangla and English, with Bangla as the default.
The owner also wants to focus on food and plants for now and bring clothing back in about two months, without deleting any code.

## Options
1. **UI translation only** (`lang/bn.json`): no schema change. Product, category and page text stays as entered.
2. **Translatable database content** (translation columns or tables for products, categories, pages): a schema redesign and a bigger admin form.

## Decision
Option 1 for now.
- `APP_LOCALE=bn`, fallback `en`. The visitor's choice is kept in a `locale` cookie (`SetLocale` middleware, `POST /language/{locale}`, switcher in the header and mobile menu).
- English strings stay the translation keys (`__('Add to Cart')`); Bangla lives in `lang/bn.json` and `lang/bn/validation.php`.
- Digits (prices, dates, counts) show as Bangla numerals in Bangla mode via `App\Support\Numerals`; stored values and form input stay ASCII.
- Storefront, customer account and the admin panel are translated (admin also opens in Bangla). Printed invoices and packing slips stay English.
- Clothing is paused with `config('shop.clothing_enabled')` (`SHOP_CLOTHING_ENABLED`, default `false`). It only gates seeding and the home promo banner; no clothing code or table was removed.

## Consequences
- New storefront text must use `__()` and get a `lang/bn.json` entry.
- Product, category and page content is single-language until a later ADR adds translatable content.
- Tests run with `APP_LOCALE=en` (phpunit.xml) so existing assertions keep working; `LanguageSwitchTest` covers Bangla.
- To bring clothing back: set `SHOP_CLOTHING_ENABLED=true` and reseed (or create the category and products in the admin).
