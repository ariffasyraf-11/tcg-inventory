# Code review and test standards

- PSR-12, Laravel conventions, Pint, straightforward naming and documented domain decisions.
- Thin Livewire components; all production stock writes in transactional actions/services.
- Owner policy authorization and workspace scoping for every query and foreign key.
- Server-side validation, input normalization, and proper mass-assignment boundaries.
- Ledger rows never overwritten/deleted for normal stock changes; corrections create new movements.
- Integer units, decimal-safe cost arithmetic, consistent FIFO ordering and nonnegative stock.
- Tests: receiving, adjustments, negative stock rejection, atomic rollback, authorization boundaries, batch reconciliation, sale allocation persistence and demo reset isolation.
- Avoid framework file rewrites or dependency changes without an explicit rationale.
- Review `composer validate`, `php artisan test`, Pint, `npm run build` before merging.
