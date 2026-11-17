# Phase 6.5 — Seller UX, images, and optional SKU for single cards

## Product decision

Add an **Enable SKU for single cards** option, initially **enabled** for backward compatibility. This is a **workspace preference** governing the single-card creation form, not a deletion or mutation of existing SKUs. Sealed products always require an SKU. Existing SKUs are retained when the preference is disabled; re-enabling must not overwrite historical values.

**Identity:** inventory, batches, sales and ledger continue to reference immutable product_variant IDs. For new singles created without an SKU, use a nullable SKU field; do not synthesize or display a fake SKU. Multiple SKU-less variants are allowed, with owner-scoped product/variant IDs as their internal identifiers. Explicit SKUs must remain unique within the workspace. Migration must preserve existing rows and existing uniqueness guarantees for non-null SKUs.

**CSV:** current `sku,quantity,unit_cost` import remains SKU-based. SKU-less singles cannot be selected by this import format and must use the authenticated inventory receipt UI. A separate ID-based CSV format is a future enhancement requiring explicit owner-scoped variant ID validation; do not silently match by name or card number.

**UX:** catalog form should explain the preference and show/hide SKU only for singles. Server validation is authoritative: sealed SKU required; single SKU required only when the workspace preference is on; provided SKU always validated and checked for uniqueness. Catalog and inventory tables show “No SKU” where appropriate, with product and variant details as the navigation target.

## Planned implementation slices

1. **6.5A — Optional single-card SKU:** additive workspace preference migration, nullable SKU migration, validation, UI toggle under settings, catalog form behavior, and tests for on/off, sealed SKU requirement, duplicate SKUs, cross-owner isolation, CSV exclusion and existing-data compatibility.
2. **6.5B — Images:** one primary image per product, upload/replace/remove, storage policy, thumbnail and placeholder; validate MIME, size, authorization and path ownership. Additional images later.
3. **6.5C — Inventory navigation:** owner-scoped product/variant details, search/filter/sort, movement history, and optional card/list view. Preserve shared sidebar and stock action semantics.

## Acceptance criteria

- An owner can turn off SKU entry for new single cards, then turn it back on without data loss.
- Singles without SKUs can be received, adjusted and sold through variant-ID-based UI flows.
- Sealed products still require SKU; duplicate explicit SKUs in a workspace are rejected.
- No SKU-less single can be imported accidentally through the SKU-based CSV workflow.
- Existing catalog, inventory and sales records continue to work after migration.
- Tests and formatting CI must pass before merging each implementation PR.

Phase 7 release hardening follows these bounded improvements; it is not blocked by optional multi-image galleries or marketplace integrations.
