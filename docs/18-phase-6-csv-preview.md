# Phase 6 — CSV validation preview

This first CSV increment is **read-only**. An authenticated seller uploads a small CSV with exactly `sku,quantity,unit_cost` headers. The server parses at most 500 data rows from a file of at most 1 MB, checks required fields, positive integer quantity, decimal unit cost and duplicate SKU within the file. It returns row-by-row feedback. No inventory data is written.

Example:

```csv
sku,quantity,unit_cost
SAMPLE-DRAGON,2,3.50
STARTER-DECK,5,12.00
```

**Not yet implemented:** owner-scoped SKU lookup, detecting duplicates against existing records, secure staging, explicit confirmation, transactional import execution and rollback, partial import policy. No upload should be described as imported until these gates are implemented and tested.
