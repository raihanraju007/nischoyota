# API

Phase 1 is server-rendered Blade ([ADR-004](decisions/ADR-004-blade-frontend.md)). JSON endpoints are added only when a feature needs them
(e.g. AJAX cart updates, search suggestions).

## Conventions

- Routes under `/api/v1/…`
- Responses use Eloquent API Resources
- Validation via FormRequest; errors use Laravel's default 422 format
- Auth: TODO

## Endpoints

| Method | URI | Purpose | Auth | Feature |
|---|---|---|---|---|
| — | — | None yet | — | — |
