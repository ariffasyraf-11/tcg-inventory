# User guide — TCG Inventory

> **Implementation status (Phase 6.5D4):** Workspace invitations, accepted memberships, switching, shared catalog browsing, shared stock receiving and adjustments, member role changes and removal are implemented. Dashboard, sales, CSV imports and settings are still owner-scoped. Phase 7 is not started.

## 1. Accounts and authentication

Register at `/register` and sign in at `/login`. Use Settings for profile and password changes. Password recovery, email verification and active-session management remain Phase 7 work.

## 2. Workspace collaboration

Every account owns a private workspace. Owners can invite users at **Invite staff** (`/workspace/invitations`) by entering an email and selecting Manager, Inventory Operator or Viewer. The system displays a one-time link for the owner to share manually; automated email delivery is not implemented. The invited user signs in with the matching email and accepts from **My invitations** (`/workspace/invitations/inbox`) or the token link.

After acceptance, use **Switch workspace** to select the shared workspace. The Catalog and Inventory pages then use that selection. The selected workspace name is shown in the navigation header. If membership is removed, the next request clears that selection and falls back to the user's own workspace.

Owners manage accepted members under **Members & roles** (`/workspace/members`), where they can change roles or remove members. Removing membership does not delete historical stock movements. Invitation history and active membership management are separate.

| Capability | Owner | Manager | Inventory Operator | Viewer |
|---|---|---|---|---|
| Browse catalog and inventory history | Yes | Yes | Yes | Yes |
| Receive and adjust stock | Yes | Yes | Yes | No |
| Create catalog products | Yes | Yes | No | No |
| Edit product information / SKU / archive | Yes | By workspace policy | By workspace policy | No |
| Manage members, invitations and SKU requirement | Yes | No | No | No |
| Sales, dashboard, CSV import | Owner-scoped only | Not shared | Not shared | Not shared |

The `catalog_edit_role` workspace setting permits owner-only, owner+manager, or owner+manager+inventory operator editing. It does not grant Viewer write access.

## 3. Catalog, SKU and product archive

Catalog supports singles and sealed products. SKU uniqueness is checked within the workspace. The owner manages whether singles require an SKU at **SKU permissions** (`/settings/sku-permissions`); sealed products always require one.

Authorized editors can change product name, active status, variant SKU and variant attributes. Attribute changes have per-field actor-attributed audit records. SKU changes are recorded with actor, previous and new value. Existing stock and sales use stable variant IDs, but previously exported CSV files are not automatically rewritten. Use **Archive product** on the edit page to remove it from the active catalog without deleting stock and sales history. The Catalog filter provides Active, Archived and All views. Reactivate by editing visibility.

## 4. Inventory and sales

Select a variant in Inventory to receive stock or record a stock adjustment. Managers and Inventory Operators can perform these operations in an accepted shared workspace. The ledger preserves the acting user. Viewers cannot mutate stock. Sales remains owner-scoped and is not automatically switched to a selected shared workspace.

## 5. CSV imports and media

CSV Preview accepts `sku,quantity,unit_cost` for owner-scoped imports; it does not support SKU-less variants. Product images can be uploaded and removed by the workspace owner. Members can view authorized catalog images.

## 6. Limitations and release readiness

Dashboard, sales, CSV imports, demo controls and settings retain their owner-scoped behavior; do not assume they operate on a selected shared workspace. The sidebar hides owner-scoped entries while a shared workspace is selected, but authorization is enforced in server controllers. No public sharing, branches, billing or ownership transfer is implemented. Phase 7 security/recovery/deployment work remains separate and not started.

## Catalog input suggestions

Condition (singles) and Language remain free-text fields with browser-native suggestion lists. Suggested condition values are Near Mint, Lightly Played, Moderately Played, Heavily Played and Damaged. Language suggestions are English and Japanese only. Users can still type other values; the suggestions are not restrictive validation. This applies to both new-product and existing-variant edit forms.

## Product-type-aware form and optional suggestions

On Add Product, choosing **Sealed product** disables the singles-only Condition, Finish and Card number inputs. Choosing **Single card** disables Sealed format. Incompatible disabled inputs are not submitted, and the SKU field becomes required for sealed products even when singles SKU is optional. Values typed into temporarily disabled inputs are retained in the browser until submission, allowing the user to switch back without retyping. The server also validates incompatible attributes.

Condition suggestions: Near Mint, Lightly Played, Moderately Played, Heavily Played, Damaged. Language suggestions: English, Japanese only. Finish suggestions: Non-Foil, Foil, Reverse Holo, Holofoil, Full Art, Textured. Sealed format suggestions: Booster Pack, Booster Box, Elite Trainer Box, Starter Deck, Structure Deck, Theme Deck, Collection Box, Tin, Bundle. All are non-restrictive text-input suggestions; Card number and SKU remain plain text without preset values. Existing-variant editing has matching Finish and Sealed format suggestions.

## Viewing variant change history

Open Catalog → Edit for a product and scroll to **Variant change history**. Authorized editors can see recorded SKU and variant-attribute changes across that product's variants, newest first, with timestamp, variant ID, field, previous value, new value and actor. The table is read-only and retains records when an actor account is deleted. It does not claim to track product name/visibility changes or initial variant creation.

## Catalog category tabs and singles SKU editing

Catalog has two tabs, **Singles & Used** and **Sealed Products**, filtering the existing product types within the selected workspace. Active/Archived/All filters and pagination preserve the selected tab. Used cards remain singles with a condition, not a new product type. SKU-less singles show their stable internal variant ID. On the edit page, singles SKU is displayed disabled/read-only; existing SKU values are preserved. Sealed products retain editable, required SKUs. The existing workspace setting still controls whether SKU is required when creating singles, and no database identifiers or historical audit entries are rewritten.

## Collapsible catalog creation and SKU-free singles

The catalog opens with the registered product list prominent. The **Add a product** disclosure is closed by default and expands automatically when validation fails. Selecting **Single card** disables SKU entry; selecting **Sealed product** enables and requires SKU. New singles created through the form are stored with no SKU and identified by internal variant ID. API submissions can still supply an optional SKU for compatibility. Previously saved singles SKUs remain untouched for historical imports and records. The workspace singles-SKU setting is retained for compatibility but no longer controls new catalog creation.
