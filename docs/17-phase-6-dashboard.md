# Phase 6 — Dashboard and low-stock monitoring

The dashboard is scoped to the authenticated owner's workspace. It summarizes stocked variants, units on hand, low-stock count, sale count, revenue and FIFO cost from persisted records. These are all-time figures, not date-filtered financial reports; currency is not yet configurable.

Each stocked variant has an editable minimum stock threshold. A variant is flagged when `quantity_on_hand <= minimum_stock_level`; zero threshold flags an empty item. Updates are owner-scoped and reject cross-owner inventory IDs.

**Remaining Phase 6:** CSV import header detection, preview, per-row validation, duplicate checks, explicit commit and atomic rollback. Do not treat the dashboard PR as Phase 6 completion.
