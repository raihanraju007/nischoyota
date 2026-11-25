# ADR-004: Server-rendered Blade + Tailwind

- **Status:** Accepted
- **Date:** 2026-09-26

## Context
An approved HTML prototype exists (`nischoyota-frontend/`). SEO matters for a shop, and the team is backend-focused.

## Decision
Blade views with Tailwind CSS 4, built by Vite. JavaScript only for progressive enhancement. No SPA framework in Phase 1.

## Consequences
- Pages are SEO-friendly by default.
- Interactive pieces (cart drawer, filters) use small JS or Blade components.
- JSON APIs are added only when a feature needs them (see [api.md](../api.md)).
