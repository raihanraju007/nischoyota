# ADR-011: Store settings in a database table

- **Status:** Accepted
- **Date:** 2026-10-06

## Context
The admin Settings page edits store name, contact details and the free-delivery threshold.
[database.md](../database.md) had put the threshold in `config/shop.php`, which an admin cannot change.

## Options
1. **`settings` key/value table, cached**: editable from the admin panel.
2. **Config file only**: simple, but every change needs a deploy.

## Decision
Option 1. A `settings` table (`key` unique, `value` json), read through a cached `SettingService` in the
`Content` module. `config/shop.php` keeps only defaults used when a key is missing.

## Consequences
- Saving settings clears the cache.
- Supersedes the "free-delivery threshold in `config/shop.php`" note in database.md §3.2.
