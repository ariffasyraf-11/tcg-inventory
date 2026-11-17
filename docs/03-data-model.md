# Data model proposal (not yet migrated)

- `users`: normal Laravel users.
- `workspaces`: id, owner_user_id, name, is_demo.
- `games`: id, name, slug.
- `products`: id, workspace_id, game_id nullable, type (single/sealed), name, active.
- `product_variants`: id, product_id, sku, condition/language/finish/card_number or sealed_format.
- `inventory_items`: id, workspace_id, variant_id, quantity_on_hand, minimum_stock_level.
- `inventory_batches`: id, inventory_item_id, quantity_received, quantity_remaining, unit_cost, received_at.
- `stock_movements`: id, inventory_item_id, batch_id nullable, signed_quantity, type, reason, actor_user_id, reference, created_at.
- `sales`, `sale_items`, `sale_item_batch_allocations`: sales with persisted FIFO cost attribution.

## Rules
Unique SKU within workspace; foreign keys; quantity >= 0; batch remaining <= received; indexes on workspace, inventory item and time. Use decimal(12,2) for currency, integer for units, and timestamps for audit. SQLite-compatible tests; MySQL production support. Deny cross-workspace reads/writes.

Do not create these tables until the first production data model PR is reviewed. The initial interactive local UX prototype uses sessions instead of these database tables.
