# Nischoyota — Documentation

This folder is the shared context for humans and AI agents. Each file has **one job**.
If a fact belongs in another file, move it there, and don't copy it.

| File | Answers | Changes |
|---|---|---|
| [prd.md](prd.md) | What are we building, and why? | Rarely |
| [architecture.md](architecture.md) | Where does code live and how do the layers connect? | When an ADR changes it |
| [rules.md](rules.md) | How must code be written? | When a convention is agreed |
| [design.md](design.md) | How should the UI look and behave? | Rarely |
| [decisions.md](decisions.md) | Why did we choose X? (ADR index) | When a decision is made |
| [tasks.md](tasks.md) | Roadmap: which features, in what order, what status | Every feature |
| [memory.md](memory.md) | What is happening **right now**? | Every session (cleaned regularly) |
| [testing.md](testing.md) | How do we verify work? | When tooling changes |
| [database.md](database.md) | Which tables exist and why? | When a migration changes the schema |
| [api.md](api.md) | Which endpoints exist? | When endpoints are added |
| [deployment.md](deployment.md) | How do we ship? | When infra changes |
| [troubleshooting.md](troubleshooting.md) | Known problems and fixes | When a problem is solved |
| [features/](features/) | Per-feature spec (`feature.md`) and task list (`tasks.md`) | Per feature |
| [workflow.md](workflow.md) | How we work with AI, step by step | Rarely |

## Where does new information go?

| Information | File |
|---|---|
| Business requirement | `prd.md` |
| Architecture / structure | `architecture.md` |
| Decision with trade-offs | `decisions/ADR-NNN-*.md` + index in `decisions.md` |
| Coding convention | `rules.md` |
| Feature requirement | `features/F-NNN-*/feature.md` |
| Feature implementation steps | `features/F-NNN-*/tasks.md` |
| Roadmap progress | `tasks.md` |
| Current state / blockers | `memory.md` |
| Bug with a non-obvious fix | `troubleshooting.md` |
