# Decisions

2026-11-17 — Start a truly new repository from the official Laravel 13 application skeleton; no code imported from earlier TCG work.

2026-11-17 — Owner scope is individuals and small businesses; single private workspace per owner for V1, no staff/branches/billing.

2026-11-17 — Use custom Livewire 4 + Blade/Tailwind for production UI; no Filament.

2026-11-17 — First demo is a local-only session UX prototype, deliberately separated from domain stock models. Replace with isolated, data-backed demo only after authorization and inventory invariants are implemented.

2026-11-17 — Track receipt cost by batches, stock with immutable ledger, sales with persisted FIFO allocations.

2026-11-17 — Keep standard Laravel project files for easy clean install; custom modules must be portable to existing clean Laravel projects.
