# ADR-010: Uploaded images on the public disk

- **Status:** Accepted
- **Date:** 2026-10-06

## Context
Categories, brands and products need uploaded images.

## Decision
Store uploads on Laravel's `public` disk (`storage/app/public`, exposed with `php artisan storage:link`).
The database stores the relative path only. No image-processing package in Phase 1; resizing and WebP
are revisited in F-030.

## Consequences
- Moving to S3/R2 later is a disk config change, because code always goes through `Storage::disk('public')`.
- Large uploads are limited by validation (type and max size) in each FormRequest.
