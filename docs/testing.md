# Testing and Verification

## Commands

| Purpose | Command |
|---|---|
| Run one test file | `php artisan test --compact tests/Feature/<Module>/<Name>Test.php` |
| Run by name | `php artisan test --compact --filter=<name>` |
| Full suite | `php artisan test --compact` |
| Format changed PHP | `vendor/bin/pint --dirty --format agent` |
| Fresh DB with seed data | `php artisan migrate:fresh --seed` |
| Check routes | `php artisan route:list --except-vendor` |
| Build assets | `npm run build` |

## Test database

**Tests never run on the development database (`nischoyota`).** They use a separate MySQL database:

| | Development | Tests |
|---|---|---|
| Database | `nischoyota` (from `.env`) | `nischoyota_testing` |
| Set in | `.env` | `phpunit.xml` (`DB_DATABASE`, `force="true"`) |

Three safeguards:

1. `phpunit.xml` forces `DB_CONNECTION=mysql` and `DB_DATABASE=nischoyota_testing`.
2. `tests/TestCase.php` **aborts every test** if the connected database name does not end in `_testing`.
3. Feature tests use `RefreshDatabase` (set in `tests/Pest.php`), so every test starts clean inside a transaction.

One-time setup on a new machine:

```sql
CREATE DATABASE nischoyota_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

If tests fail with "Refusing to run tests against database …", the config is cached. Run `php artisan config:clear`.

## What to test

| Kind | Location | Covers |
|---|---|---|
| Feature | `tests/Feature/<Module>/` | HTTP flow: happy path, validation errors, authorization (guest / wrong user / admin), redirects, DB state |
| Unit | `tests/Unit/<Module>/` | Pure business rules in services: prices, totals, stock, discounts |

- Use factories and their states; don't insert rows by hand.
- One behaviour per test, named as a sentence: `it('rejects checkout when stock is insufficient')`.
- Money and stock logic always get edge-case tests (zero, negative, rounding, concurrency where relevant).

## Verification checklist (per feature)

1. Narrow tests for the feature pass
2. `pint --dirty` clean
3. `migrate:fresh --seed` succeeds (if migrations changed)
4. Manual check of the affected pages in the browser (JS behaviour — variant picker, filters, dropdowns — can also be checked with headless Chrome via the DevTools protocol; Node 22 has `WebSocket` built in, so no package is needed)
5. User runs the full suite
