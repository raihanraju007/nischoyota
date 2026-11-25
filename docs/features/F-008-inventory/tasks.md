# F-008: Inventory — Tasks

## Database
- [x] Migration `stock_movements`; `StockMovement` model, factory; `StockMovementReason` enum

## Backend
- [x] `InventoryServiceProvider`, `InventoryRepository`, `InventoryService` (adjust, opening stock), `InsufficientStockException`
- [x] `ListStockRequest`, `AdjustStockRequest`, `Admin\InventoryController`, routes
- [x] Catalog: opening stock via InventoryService; no stock writes on product/variant update

## Frontend
- [x] Inventory list + variant page; sidebar link; read-only stock in product form / variants table

## Tests
- [x] Feature: adjust (each reason), negative guard, equal count, history, list tabs/search, opening stock, read-only stock, authorization
- [x] Concurrency-relevant: lock used (service test)

## Verification / Documentation
- [x] Tests, pint, migrate:fresh --seed, build, HTTP check; docs
