# Phase 6.5D — Workspace and collaboration

**Status: active working phase, not implemented.** This moves collaboration from the deferred Post-MVP backlog into the pre-Phase-7 roadmap. The currently deployed access model remains one owner per workspace until implementation and authorization tests pass.

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

**Owner is not a removable membership role.** Existing owner relationship remains authoritative. Manager access to sensitive financial details and sales privileges should be explicitly reviewed before coding.

## Delivery slices

1. **6.5D1 — Membership schema and policies:** workspace memberships with unique workspace/user pair, explicit role values, owner-access compatibility, workspace context resolution, and tests for no cross-tenant reads or writes.
2. **6.5D2 — Invitations:** owner-only invite creation, expiring single-use tokens stored hashed, accept/decline/revoke, duplicate and expired invitation handling, no user enumeration. Account verification/mail dependency must be resolved without claiming Phase 7 email verification exists.
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
