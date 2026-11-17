# Phase 6 — Confirmed stock receipt from CSV

The upload preview checks CSV structure and file-level duplicate SKUs, then matches every SKU to **exactly one** variant in the authenticated owner's real workspace. Unknown or ambiguous SKUs block confirmation. Validated rows are held in the user's server-side session, associated with a random token and the authenticated owner/workspace.

The confirmation POST requires CSRF and the matching token. The import action rechecks owner, workspace, SKU identity and row values, and calls the existing `StockOperations::receive` for each row inside one outer database transaction. If any row fails, all receipt batches, item increments and ledger entries roll back. A successful commit invalidates the staged session token.

**Boundaries:** CSV creates receipts for existing SKUs only; it does not create catalog products. Up to 500 rows and 1 MB per upload. The session-based staging is intended for one in-flight preview per browser session, not a durable background queue. It does not yet provide durable import history or a server-side idempotency key for network retries. Review large-batch concurrency and ledger traceability before production release.
