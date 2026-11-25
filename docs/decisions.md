# Architecture Decisions (ADR index)

Settled decisions. **AI must not reopen an Accepted ADR.** To change one, write a new ADR that supersedes it.

| ADR | Decision | Status | Date |
|---|---|---|---|
| [001](decisions/ADR-001-modular-monolith.md) | Laravel modular monolith (`app/Modules/*`) | Accepted | 2026-09-26 |
| [002](decisions/ADR-002-mysql.md) | MySQL 8 as the only database | Accepted | 2026-09-26 |
| [003](decisions/ADR-003-service-repository.md) | Controller → Service → Repository → Model | Accepted | 2026-09-26 |
| [004](decisions/ADR-004-blade-frontend.md) | Server-rendered Blade + Tailwind | Accepted | 2026-09-26 |
| [005](decisions/ADR-005-phase-1-single-vendor.md) | Phase 1 is single-vendor | Accepted | 2026-09-26 |
| [006](decisions/ADR-006-vendor-agnostic-core.md) | Vendor/supplier concepts stay out of unrelated logic | Accepted | 2026-09-26 |
| [007](decisions/ADR-007-database-design-phase-1.md) | Phase 1 database design (D1–D9) | Accepted | 2026-10-06 |
| [008](decisions/ADR-008-module-map.md) | Module map for Phase 1 | Accepted | 2026-10-06 |
| [009](decisions/ADR-009-auth-without-package.md) | Auth on Laravel core, no auth package | Accepted | 2026-10-06 |
| [010](decisions/ADR-010-image-storage.md) | Uploaded images on the public disk | Accepted | 2026-10-06 |
| [011](decisions/ADR-011-settings-table.md) | Store settings in a database table | Accepted | 2026-10-06 |
| [012](decisions/ADR-012-admin-roles.md) | Admin and staff roles | Accepted | 2026-10-06 |
| [013](decisions/ADR-013-inventory-owns-stock.md) | Inventory owns the stock column | Accepted | 2026-10-06 |
| [014](decisions/ADR-014-bangla-default-language.md) | Bangla default language, clothing paused | Accepted | 2026-11-25 |

New ADR: copy [decisions/_template.md](decisions/_template.md) to `decisions/ADR-NNN-short-name.md` and add a row here.
Status values: `Proposed` → `Accepted` / `Rejected` → later `Superseded by ADR-NNN`.
