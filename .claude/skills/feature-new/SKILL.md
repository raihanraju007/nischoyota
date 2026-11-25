---
name: feature-new
description: Create a new feature spec folder (docs/features/F-NNN-name/ with feature.md and tasks.md) from the templates. Use when the user wants to start, define or specify a new feature.
argument-hint: <feature name or short description>
disable-model-invocation: true
---

# New feature: $ARGUMENTS

Goal: produce a **Draft** spec the user can approve. Do not write any application code.

1. Read `docs/prd.md`, `docs/tasks.md`, `docs/features/README.md` and `docs/decisions.md`.
2. Pick the next free number `F-NNN` (zero-padded, from the index in `docs/features/README.md`). Folder: `docs/features/F-NNN-<kebab-name>/`.
3. Copy `docs/features/_template/feature.md` and `tasks.md` into it.
4. Fill in `feature.md` from what the PRD and the user said. Also look at:
   - the matching static views in `resources/views/` and prototype pages in `nischoyota-frontend/`, for UI requirements;
   - existing code, for dependencies.
   Anything you are not sure about goes into **§13 Open questions**. Don't invent business rules.
5. Tailor `tasks.md` to this feature. Remove steps that don't apply and add feature-specific ones.
6. Add a row to the index in `docs/features/README.md` (status Draft). Link the roadmap item in `docs/tasks.md` to the folder.
7. Reply with a short summary and the list of open questions for the user to answer.
