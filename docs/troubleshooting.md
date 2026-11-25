# Troubleshooting

Known problems with non-obvious fixes. Format: **Symptom** → **Cause** → **Fix**.

### Vite manifest error ("Unable to locate file in Vite manifest")
- **Cause:** assets not built.
- **Fix:** `npm run build`, or keep `npm run dev` running.

### `npm run build` fails: "node:util does not provide an export named 'styleText'"
- **Cause:** Vite 8 needs Node 20.19+ / 22; the shell's default Node is 18.
- **Fix:** `nvm use 22` (or `nvm alias default 22`), then build again.

### Tests fail with "SQLSTATE[HY000] [2002] Connection refused"
- **Cause:** MySQL is not running (tests use `nischoyota_testing` on 127.0.0.1:3306).
- **Fix:** start MySQL (Docker Desktop / local service), then `php artisan test --compact`.

### Eloquent collection `only()` / `except()` ignore your custom keys
- **Symptom:** after `$models->keyBy(...)`, `except($keys)` / `only($keys)` remove the wrong items, or `keys()` returns 0, 1, 2…
- **Cause:** `Illuminate\Database\Eloquent\Collection::only()/except()` match **model primary keys** and re-index the result.
- **Fix:** call `->toBase()` before `keyBy()` when the keys are not model ids (see `ProductVariantService::syncCombinations`).

### Pest `expect($model)->value` checks the wrong thing
- **Symptom:** `Call to undefined method …::toBe()` or a wrong value when a model has a `value` column (coupons, attribute values).
- **Cause:** Pest's `Expectation` has its own `value` property; `->value` returns the model, not the column.
- **Fix:** write `expect($model->value)->toBe(…)` instead of the higher-order form.

### Where is the SMS code in development?
- **Cause:** `SMS_DRIVER=log` (default until a provider is chosen, G-6) writes SMS to the log instead of sending them.
- **Fix:** `grep "SMS to" storage/logs/laravel.log | tail -1`. Email codes go to the same log with `MAIL_MAILER=log`.

### Order dates jump by 6 hours / OTP codes expire at once (MariaDB)
- **Symptom:** `orders.placed_at` changes on every status update (it shows a later time than the history), or a phone code is "expired" right after a wrong try.
- **Cause:** with `explicit_defaults_for_timestamp = OFF` (MariaDB, older MySQL), the **first NOT NULL `timestamp` column** of a table silently gets `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`, filled with the server's local time.
- **Fix:** give such columns an explicit default: `$table->timestamp('…')->useCurrent()` (migration `stop_auto_updating_order_and_otp_timestamps`, F-025). Check with `SHOW CREATE TABLE`. New NOT NULL timestamp columns must always use `useCurrent()` or be nullable.
