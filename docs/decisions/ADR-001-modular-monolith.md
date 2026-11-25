# ADR-001: Laravel modular monolith

- **Status:** Accepted
- **Date:** 2026-09-26

## Context
The shop will grow from single-vendor to multi-vendor and dropshipping. A single flat `app/` would become hard to navigate, and microservices are too heavy for a small team.

## Decision
One Laravel application. Business code lives in `app/Modules/<Module>/`, each with its own Http, Models, Services, Repositories, Policies, Providers and routes. Migrations, views and tests stay in Laravel's default folders. Layout: [architecture.md](../architecture.md).

## Consequences
- Clear boundaries: modules talk through services or events, not each other's repositories.
- Each module registers its own service provider and routes.
- New modules copy the structure of an existing one.
