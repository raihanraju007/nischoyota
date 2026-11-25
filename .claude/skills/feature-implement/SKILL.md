---
name: feature-implement
description: Implement an approved feature (F-NNN) following docs/rules.md, task group by task group, with verification. Use when the user asks to implement or build an approved feature.
argument-hint: <F-NNN> [task group, e.g. "Database"]
disable-model-invocation: true
---

# Implement $ARGUMENTS

1. Read `docs/features/<F-NNN>*/feature.md` and `tasks.md`, plus `docs/rules.md` and `docs/architecture.md`. If `feature.md` status is still `Draft` or has unanswered open questions that affect this work, **stop and ask**.
2. Activate the relevant skills: `laravel-best-practices` for PHP, `testing-best-practices` for tests, `tailwindcss-development` for views.
3. Inspect existing code and follow its patterns. Use `php artisan make:*` with `--no-interaction` to create files.
4. Work **one task group at a time** (or only the group named in the arguments). For each group:
   - implement it with the layering in `docs/rules.md` §1 (Controller → FormRequest → Service → Repository → Model)
   - write or extend tests
   - run the narrow tests and `vendor/bin/pint --dirty --format agent`
   - tick the finished items in the feature's `tasks.md`
5. Stay inside scope:
   - Don't touch unrelated modules.
   - Don't add packages.
   - Don't build Phase 2/3 features.
   - If an architectural decision comes up, stop and propose it instead of deciding.
6. Set the `feature.md` status to `In progress` (or `In review` when all groups are done).
7. End with this report:

```
Files changed:    …
Tests executed:   <command> → <result>
Result:           …
Known issues:     …
Remaining work:   …
```

Offer to commit the finished group. Don't commit without the user's OK.
