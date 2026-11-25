---
name: sync-docs
description: Update project docs at the end of a work session. Ticks tasks, updates feature status, roadmap and memory.md, and moves permanent facts to their proper files. Use when the user says to sync, update or close out docs.
disable-model-invocation: true
---

# Sync docs

1. Look at what changed: `git status`, `git log` since the last update noted in `docs/memory.md`, and this conversation.
2. Update, only where something actually changed:
   - the feature's `tasks.md` checkboxes and `feature.md` status
   - the `docs/features/README.md` index status
   - the roadmap `docs/tasks.md`
   - `docs/architecture.md` modules table (new module), `docs/api.md` (new endpoints), `docs/troubleshooting.md` (a solved non-obvious problem)
3. Rewrite `docs/memory.md` so it shows **only current context**: phase, current feature, current work, recently completed (last few items), blockers, next. Set "Last updated" to today.
4. If a decision was made this session, ask whether to record it as an ADR. If a coding convention was agreed, ask whether to add it to `docs/rules.md`. Don't add either without the user's OK.
5. Show a short summary of what was updated.
