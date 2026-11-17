# Product scope and acceptance

## Intended users
Solo collectors who sell cards, individual sellers, and small shops. One private owner workspace per account for the first release; no enterprise admin console.

## MVP
- Games, card singles, sealed products, and SKU-level variants (condition, language, finish, edition).
- Integer on-hand quantities, purchase/receipt batches, decimal costs and low-stock thresholds.
- Receive stock, adjust in/out with mandatory reason and actor identity, chronological immutable stock movement ledger.
- FIFO costing when a sale is recorded, persisting each sale-line-to-batch allocation.
- Dashboard, simple manual sales and carefully validated CSV imports after the core system works.
- Responsive, accessible custom Livewire/Blade UX.

## Non-goals for the first release
Subscriptions, paid billing, staff roles, branches, marketplace sync, scraping product prices, advanced accounting, complex order fulfilment.

## Inventory invariants
On-hand quantity never negative. Every stock mutation is atomic and auditable. Batch remaining quantities reconcile with stock on hand. Inventory ownership must be enforced for reads and writes. Monetary amounts use decimal arithmetic (not floating-point calculations).

## Acceptance
An owner can manage singles and sealed inventory, receive five units, adjust down one unit with reason, and immediately see accurate quantity and history. The local UX prototype is distinct from production inventory and must not be treated as proof of stock integrity.
