# F-NNN: Feature Name — Tasks

Status: `[ ]` todo · `[~]` doing · `[x]` done. Tick items as they finish. Commit after each green group.

## Database
- [ ] Migration(s)
- [ ] Model(s): relations, casts, `$fillable`
- [ ] Factory (+ states)
- [ ] Seeder (if reference data)

## Backend
- [ ] Repository
- [ ] Service
- [ ] FormRequest(s)
- [ ] Policy
- [ ] Controller(s)
- [ ] Routes (module `routes/*.php`)

## Frontend
- [ ] Replace `Route::view()` / static data with controller data
- [ ] Blade views and components
- [ ] Validation error and empty states

## Tests
- [ ] Feature: happy path
- [ ] Feature: validation
- [ ] Feature: authorization
- [ ] Unit: business rules
- [ ] Edge cases from `feature.md` §10

## Verification
- [ ] Narrow tests pass
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] Manual browser check

## Documentation
- [ ] `feature.md` status → Done
- [ ] Roadmap `docs/tasks.md` updated
- [ ] `docs/memory.md` updated
- [ ] `docs/architecture.md` modules table (if a new module)
