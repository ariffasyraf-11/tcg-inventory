# Phase 6.5D — Workspace and collaboration

**Status: D1 merged; D2 invitation workflow in development; workspace switching and shared application access not implemented.** This moves collaboration from the deferred Post-MVP backlog into the pre-Phase-7 roadmap. The existing request controllers continue to use owner-only workspace resolution. D1 adds schema and a standalone authorization service, but deliberately does not enable shared access until later slices audit and adopt the service.

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
2. **6.5D2 — Invitations (in development):** owner-only invite creation, expiring single-use tokens stored hashed, accept/decline/revoke, duplicate and expired invitation handling, no user enumeration. D2 uses authenticated email-matched acceptance and an owner-copied one-time link (no automated email delivery or Phase 7 email verification yet). Link is displayed once and only its SHA-256 hash is stored.
3. **6.5D3 — Role-aware UI and workspace switching:** member-scoped navigation, selected-workspace context, role checks on every server endpoint, and authorized member removal.
4. **6.5D4 — Regression and documentation:** stock/sales actor audit, demo isolation, cross-owner attacks, concurrent access, membership revocation, and updated user guide/test account scenarios.

## Safety and acceptance

- All existing owner-only endpoints must be audited before enabling member access; changing sidebar visibility alone is insufficient.
- Every request resolves an authorized workspace; product/variant/item IDs from another workspace must fail closed.
- Never migrate stock ownership to user IDs or silently grant access to all accounts.
- Invitation tokens are one-time, expiring, revocable and never logged in plaintext.
- Removing a member does not delete historical stock movements or sales.
- Keep Phase 7 separate until 6.5C and 6.5D are implemented and regression-tested.

See [22-user-guide-workspaces-and-collaboration.md](22-user-guide-workspaces-and-collaboration.md) for the current user-facing implementation status.
