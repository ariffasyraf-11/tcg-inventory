# Architecture

## Components
Standard Laravel 13 monolith; Livewire 4 for authenticated inventory screens; Blade and Tailwind for presentation. Eloquent for persistence and policy-backed owner scoping. Stock writes belong in focused action/service classes, not Livewire component methods.

Suggested later structure:

```text
app/Actions/Inventory/ReceiveStock.php
app/Actions/Inventory/AdjustStock.php
app/Actions/Sales/RecordSale.php
app/Services/Inventory/FifoAllocator.php
app/Livewire/Inventory/
app/Livewire/Products/
app/Models/
app/Policies/
database/seeders/DemoInventorySeeder.php
tests/Feature/Inventory/
```

## Stock consistency
Use database transactions and row locks for receipt, decrement, FIFO allocation and sale capture. Validate workspace ownership and business rules inside actions. Store append-only movements with actor, reason, signed quantity and reference. Reconcile inventory item quantity with remaining batch quantities.

## Demo boundary
The initial demo is a **local-only, session-scoped UX prototype** with fictional sample data, not the persistent inventory domain. Its routes verify the environment server-side. Production demo workspaces, if introduced later, must be isolated from real owner data and must have safe reset authorization. No shared editable public demo login.
