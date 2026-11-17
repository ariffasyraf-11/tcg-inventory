# Phased build roadmap

| Phase | Deliverable | Exit gate |
|---|---|---|
| 0 | Clean Laravel 13 skeleton, setup docs, local demo prototype | Dependencies install and local demo route tests pass |
| 1 | Livewire 4 integration, authentication, owner workspace, catalog | Auth and cross-workspace tests pass |
| 2 | Stock item, batches, immutable ledger, receipt/adjust actions | Atomic writes and reconciliation tests pass |
| 3 | Real Livewire inventory screens, demo seed workspace | Demo journey works against isolated DB data |
| 4 | Sales and persisted FIFO batch allocations | Cost and negative-stock tests pass |
| 5 | Dashboard, thresholds, CSV import preview | No silent partial imports |
| 6 | Security, CI, deployment and backup docs | Release checklist passes |

Each phase should be a small PR; no direct merge without test/build review. Phase 0 UI session operations are deliberately not production stock writes.
