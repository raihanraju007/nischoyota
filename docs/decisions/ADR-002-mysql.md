# ADR-002: MySQL 8

- **Status:** Accepted
- **Date:** 2026-09-26

## Context
We need a relational database with good hosting availability and team familiarity.

## Decision
MySQL 8 is the only database for all environments. Tests use MySQL-compatible features only; no SQLite-only or Postgres-only syntax.

## Consequences
- JSON columns, CTEs and window functions are available.
- The test database setup is documented in [testing.md](../testing.md).
