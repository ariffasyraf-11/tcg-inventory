# Phase 2: inventory domain foundation

The internal `StockOperations` action handles costed receipts and negative stock adjustments. There are no production stock HTTP routes or Livewire screens yet.

- One `inventory_items` row per variant, scoped to the owning workspace.
- Costed `inventory_batches` track received and remaining integer units.
- `stock_movements` capture actor, reason, signed quantity and batch references.
- All writes use database transactions, item row locking and reconciliation before and after each mutation.
- Adjustments out consume oldest batches first; positive adjustments must be recorded as costed receipts.
- The owner is resolved from the authenticated user, not client-supplied workspace IDs.
- Demo workspaces cannot be modified by the stock action.

## Follow-up risks

The ledger is append-only by application convention, not protected against direct SQL updates/deletes. Concurrent first receipts require unique-key retry handling. Database-level workspace SKU uniqueness, stronger DB constraints, MySQL concurrency tests and audit retention must be completed before production. UI integration belongs to Phase 3.
