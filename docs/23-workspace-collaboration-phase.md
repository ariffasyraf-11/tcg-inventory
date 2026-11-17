# Phase 6.5D — Workspace and collaboration

**Status: D1/D2 and product edit/inbox merged; D3 workspace selection and scoped catalog/inventory browsing in development. Stock operations for manager and inventory operator are being enabled with explicit selected-workspace authorization; sales and financial screens remain owner-only.** This moves collaboration from the deferred Post-MVP backlog into the pre-Phase-7 roadmap. The existing request controllers continue to use owner-only workspace resolution. D1 adds schema and a standalone authorization service, but deliberately does not enable shared access until later slices audit and adopt the service.

## Goal and boundaries

Enable an owner to invite staff to a workspace while retaining private stock and financial data. Preserve immutable inventory movement actor attribution and owner isolation. Avoid an unbounded organization/admin platform: no billing, branches, public workspace discovery or ownership transfer in the first collaboration slice.

## Proposed role matrix (requires approval during implementation)

| Action | Owner | Manager | Inventory operator | Viewer |
|---|---|---|---|---|
| View catalog, stock and history | Yes | Yes | Yes | Yes |
| Receive and adjust stock | Yes | Yes | Yes | No |
| Record manual sales | Yes | Yes | Proposed yes | No |
| Manage product catalog | Yes | Yes | No | No |
| Manage invitations and roles | Yes | No | No | No |
| Workspace settings | Yes | No | No | No |

**Owner is not a removable membership role.** Existing owner relationship remains authoritative. The initial authorization service reserves sales to owner/manager; inventory operators do not receive sales permission. Manager access to sensitive financial details requires review before activation.

## Delivery slices

1. **6.5D1 — Membership schema and policies (in development):** workspace memberships with unique workspace/user pair, explicit role values, owner-access compatibility, workspace context resolution, and tests for no cross-tenant reads or writes.
2. **6.5D2 — Invitations (in development):** owner-only invite creation, expiring single-use tokens stored hashed, accept/decline/revoke, duplicate and expired invitation handling, no user enumeration. D2 includes authenticated email-matched acceptance and an owner-copied one-time link (no automated email delivery or Phase 7 email verification yet). Link is displayed once and only its SHA-256 hash is stored. A follow-up recipient inbox at `/workspace/invitations/inbox` lists invitations for the authenticated email and permits accept/decline without requiring the original token. No email delivery is claimed.
3. **6.5D3 — Role-aware UI and workspace switching (in development):** member-scoped navigation, selected-workspace context, role checks on every server endpoint, and authorized member removal.
4. **6.5D4 — Regression and documentation:** stock/sales actor audit, demo isolation, cross-owner attacks, concurrent access, membership revocation, and updated user guide/test account scenarios.

## Product information editing policy

A workspace-scoped `catalog_edit_role` setting controls the minimum staff role allowed to edit product information: `owner` (owner only), `manager` (owner and manager), or `inventory_operator` (owner, manager and inventory operator). Viewer never edits. The default is manager. This is not a system-wide toggle; every seller controls their own workspace. Server-side checks remain mandatory.

The first edit slice updates **product name and active status only**. Type, SKU and variant attributes remain immutable through this form until dedicated inventory-safe change handling and audit rules are designed. Existing owner-only routing means this setting does not yet grant members access.

## D3 incremental rollout

The first workspace-switching slice allows members to select an accepted workspace and browse its catalog and inventory history. Catalog creation remains restricted to owner/manager by policy. Stock receiving and adjustments are authorized for owner/manager/inventory operator against the selected workspace in the D3 stock slice, with actor attribution retained. Sales and CSV imports remain owner-scoped. Dashboard, sales, CSV import, image mutation and settings retain their existing owner-scoped behavior and do not automatically inherit the selected workspace. Revoked membership invalidates selected context on the next request.

## Dedicated SKU permissions

Workspace owners manage SKU requirements at `/settings/sku-permissions`. The page separates the current enforceable SKU-required toggle from the future permission to change an existing SKU. Existing SKU editing remains disabled until a safe rename workflow with uniqueness and import reference checks is developed. This setting is scoped to the owner workspace and cannot be changed by invited members.

## Safety and acceptance

- All existing owner-only endpoints must be audited before enabling member access; changing sidebar visibility alone is insufficient.
- Every request resolves an authorized workspace; product/variant/item IDs from another workspace must fail closed.
- Never migrate stock ownership to user IDs or silently grant access to all accounts.
- Invitation tokens are one-time, expiring, revocable and never logged in plaintext.
- Removing a member does not delete historical stock movements or sales.
- Keep Phase 7 separate until 6.5C and 6.5D are implemented and regression-tested.

See [22-user-guide-workspaces-and-collaboration.md](22-user-guide-workspaces-and-collaboration.md) for the current user-facing implementation status.

## D3 member management

Owner-only `/workspace/members` lists active memberships, allows role changes and removes members. Membership removal revokes workspace selection on the next request while preserving historical movement actor IDs. All mutation endpoints verify the membership belongs to the authenticated owner's workspace. Invitation history is separate from active membership management.

## D3 catalog maintenance: SKU changes and product archive

The product edit screen supports audited SKU changes, workspace-level duplicate checks, and SKU-required rules for sealed products and single cards. Historical stock and sales continue to refer to immutable variant IDs. Old SKU values are retained in `product_sku_changes` for traceability. External CSV files are **not** rewritten; users must update old references. The Remove action archives a product by setting `active=false`, rather than deleting records with historical stock or sales. Products can be reactivated via Edit. Both actions enforce selected-workspace authorization and the catalog-edit policy.

## D3 variant attribute editing

Authorized catalog editors can update condition, language, finish and card number for singles, or language and sealed format for sealed products. Product type, variant ID, stock and costs are not edited here. Each changed field creates an actor-attributed before/after record in `product_variant_attribute_changes`. The edit page warns that existing stock and sales still point to the same variant ID and will display the current attributes; historical attribute values are preserved in the audit table. Variant merging and permanent deletion are out of scope.
