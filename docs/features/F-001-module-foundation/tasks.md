# F-001: Module foundation — Tasks

Status: `[ ]` todo · `[~]` doing · `[x]` done. Tick items as they finish. Commit after each green group.

## Backend
- [x] `App\Providers\ModuleServiceProvider` (abstract) with `loadModuleRoutes()`
- [x] `App\Support\Money`
- [x] Model strictness in `AppServiceProvider`

## Frontend
- [x] `<x-money>` component
- [x] `<x-flash-toasts>` component in the three layouts + JS that shows them

## Tests
- [x] Unit: `Money` format / convert / edge cases
- [x] Feature: fixture module routes (web + admin prefix and names)
- [x] Feature: flashed message renders as a toast; `<x-money>` output
- [x] Feature: lazy loading is prevented

## Verification
- [x] Narrow tests pass
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `npm run build`

## Documentation
- [x] `architecture.md`: "Adding a module"
- [x] `feature.md` status → Done
- [x] Roadmap `docs/tasks.md` updated
- [x] `docs/memory.md` updated
