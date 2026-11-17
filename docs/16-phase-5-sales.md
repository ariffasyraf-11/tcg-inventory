# Phase 5 — Manual sales and FIFO allocations

## Scope

Authenticated owner records one variant per sale, quantity, unit selling price and optional reference. The service locks the inventory item and FIFO batches, verifies their balance, persists the sale header, sale line, allocation rows, per-batch stock movement entries and decremented inventory balances in **one transaction**.

Revenue and cost are recorded as decimal amounts. Unit costs from receipt batches determine COGS; sale prices determine revenue. All allocations preserve their original unit cost. Overselling and cross-owner writes are rejected.

## Boundaries

This increment supports **single-line sales only**; multi-line checkout, refunds, tax, discounts, currency policy and payment integration are out of scope. Financial amount caps and integer arithmetic need further hardening before production use. Inventory movements remain append-only by application convention; database writes should be restricted to service methods.

## Acceptance tests

Two receipt batches (2 units at 3.00 and 3 units at 5.00) sold as 4 units at 10.00 should produce revenue 40.00, FIFO COGS 16.00, two allocations and one unit remaining. Reject quantity exceeding stock and cross-workspace variants.
