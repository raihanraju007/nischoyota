# AI Engineering Workflow

```
PRD ─▶ Feature spec ─▶ Explore ─▶ Plan ─▶ Implement ─▶ Verify ─▶ Review ─▶ Update docs
(why)   (what)          (AI,      (human   (AI)          (tests)   (AI +     (tasks,
                         read-     approves)                        human)    memory)
                         only)
```

## The loop, per feature

| Step | Who | Command | Output |
|---|---|---|---|
| 1. Define | Human (+AI help) | `/feature-new <name>` | `docs/features/F-NNN-name/feature.md` + `tasks.md` |
| 2. Explore | AI, **no code changes** | `/feature-explore F-NNN` | Report: existing code, dependencies, risks, proposed plan |
| 3. Approve | Human | — | Plan accepted, or edited; open questions answered in `feature.md` |
| 4. Implement | AI | `/feature-implement F-NNN` | Code + tests, one task group at a time |
| 5. Verify | AI runs, human checks | part of step 4 | Test/lint output in the report |
| 6. Review | AI, **no code changes** | `/feature-review F-NNN` | Issues by severity |
| 7. Close | AI | `/sync-docs` | `tasks.md`, `memory.md`, feature status updated |

## Change size decides the process

| Change | Process |
|---|---|
| Small (bug fix, copy, one-file change inside an existing pattern) | Analyze → implement → verify |
| Feature (new behaviour inside current architecture) | Full loop above |
| Architectural (new module boundary, new package, schema redesign, anything touching the multi-vendor path) | Analyze → **propose ADR** → human approves → implement |

AI must **never** make an architectural decision silently. If it discovers one is needed, it stops and
writes the proposal (current state, requested change, impact, options, recommendation).

## Every implementation report ends with

```
Files changed:    …
Tests executed:   …  (command + result)
Result:           …
Known issues:     …
Remaining work:   …
```

"It should work" is not a result. Only command output counts.

## Session hygiene

- One feature per session where possible. Start with `docs/memory.md`.
- Commit after each task group that is green (tests + pint).
- End the session with `/sync-docs`.
