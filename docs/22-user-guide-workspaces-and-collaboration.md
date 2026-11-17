# User guide — TCG Inventory

> **Implementation status:** this guide distinguishes working MVP features from future proposals. Post-MVP collaboration is **not implemented**. Do not assume that invitations, staff access, shared ownership or roles exist.

## 1. Accounts and sign-in (implemented)

- Visit `/register` to create a seller account, then `/login` to sign in.
- The authenticated workspace sidebar provides Dashboard, Catalog, Inventory, Sales, CSV Preview, Demo and Settings.
- Use **Log out** in the shared navigation to end the session.
- Password change is available under Settings with current-password verification. Password reset, email verification and active-session management are planned for Phase 7, not currently available.

## 2. Workspaces (implemented: one owner, one private workspace)

A **workspace** is the seller's private container for catalog products, variants, inventory items, receipt batches, stock movements and sales. It is linked to exactly one owner account. On first access, the application creates the owner's workspace automatically if it does not exist. The workspace name is editable in Settings.

**Current permission model:** the signed-in owner may view and modify only their own workspace's real inventory. A second account has a different private workspace, even if it creates the same SKU. There is no workspace switcher, organization directory, invitation acceptance, membership management or role assignment. A demo workspace is intentionally isolated and read-only where applicable; the local session demo is separate from seller data.

| Capability | Current MVP owner | Other account | Post-MVP collaborator |
|---|---|---|---|
| View owner's catalog and stock | Yes | No | Not implemented |
| Receive/adjust stock | Yes | No | Not implemented |
| Record sales | Yes | No | Not implemented |
| Change workspace settings | Yes | No | Not implemented |
| Invite users or manage roles | No | No | Not implemented |
| Switch between shared workspaces | No | No | Not implemented |

See [10-test-accounts.md](10-test-accounts.md) for independent test identities and isolation checks.

## 3. Catalog and single-card SKUs

Open **Catalog** to create a single card or sealed product. Record the product name, type and relevant variant attributes (condition, language, finish, card number or sealed format). Explicit SKUs must be unique within the workspace.

**Phase 6.5A (implemented):** under Settings, an owner can choose whether new single cards require an SKU. Default is enabled. Disabling it permits SKU-less singles, without changing existing SKUs. Sealed products still require an SKU. SKU-less singles are identified internally by variant ID and remain eligible for normal stock operations. The existing CSV import cannot target them.

## 4. Inventory and sales (implemented)

Open **Inventory** to select a product variant, receive a positive quantity with unit cost, or adjust stock out with a reason. On-hand quantities reconcile against receipt batches. The system records an audit movement for each receipt or adjustment. Open **Sales** to record a manual sale, quantity and unit price; FIFO batch allocation determines cost. These are owner-scoped operations; the system prevents overselling.

## 5. CSV stock receipts (implemented, Phase 6C)

Open **CSV Preview** and upload a CSV with the exact header `sku,quantity,unit_cost`. Up to 500 rows and a 1 MB file are accepted. Every SKU must resolve uniquely within the owner's catalog. Review validation issues before confirming. **Confirm stock import** writes all valid receipts in one transaction; a failure rolls back the batch. CSV does not create new catalog products and does not support SKU-less variants.

## 6. Product images and enhanced inventory navigation

**Phase 6.5B (in development):** the Catalog table supports uploading, replacing and removing one primary image per product (JPEG, PNG, WebP or GIF; maximum 3 MB). Images are private to the owner workspace and displayed as thumbnails; missing images use a placeholder. These controls become available only after the image PR is merged.

**Phase 6.5C (in development):** product/variant detail pages, inventory search/filter and movement-history navigation remain planned.

## 7. Workspace and collaboration — Phase 6.5D (active planning; not implemented)

**Status: active working phase, not yet implemented.** No invitation, shared-workspace membership, staff role or permissions UI is currently implemented. Do not share the owner password as a workaround.

Proposed future workflow, subject to product approval:
1. An owner invites a colleague by email to a specific workspace.
2. The colleague accepts with their own account; membership is scoped to that workspace.
3. An owner assigns a limited role (for example Viewer, Inventory Operator or Manager) according to an explicit permission matrix.
4. Every inventory action records the actual actor, and authorization checks both membership and role.
5. The owner can revoke access; revocation must take effect for subsequent requests without deleting audit history.

**Architecture work needed before implementation:** a workspace-membership join table, invitation lifecycle, role/permission policy, ownership-transfer safeguards, cross-workspace access checks, revocation behavior, and tests for concurrent users and account separation. These are proposals, not existing routes or tables. Current access remains owner-only. See [23-workspace-collaboration-phase.md](23-workspace-collaboration-phase.md).

## 8. Roadmap and release caveats

Phase 6.5A is merged; image and inventory UX slices remain in progress/planned. Phase 7 must cover account recovery, verification, session management, security and backup/deployment before production readiness is claimed. See [05-roadmap.md](05-roadmap.md) and [21-phase-6-5-ux-and-optional-sku.md](21-phase-6-5-ux-and-optional-sku.md).
