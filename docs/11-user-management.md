# User management architecture and delivery

## Current behavior (merged PR #2)

Laravel session authentication supports registration, login and logout with login/register throttling. One user owns one private workspace, created on first catalog access. Product queries are scoped to that workspace. There is no user administration screen, team access, password recovery, email verification workflow, session management UI or 2FA in the current release.

## Approved MVP decisions

- Owner-only access; one workspace per account.
- User identity and workspace inventory are separate concepts: users authenticate; workspaces own products and stock.
- No staff/manager/viewer roles or multi-workspace switching in V1.
- Profile and workspace settings in Phase 3; security hardening before production release in Phase 7.
- Stock and sale audit records retain actor IDs even when user status changes; account closure must not erase audit evidence.

## Phase 2 authorization contract

Resolve workspace from the authenticated owner, not from a trusted client-supplied workspace ID. Validate every product, variant, inventory item and batch belongs to the same workspace before mutation. Record the actor on stock movements. Prefer policy or service-level checks alongside route middleware; never rely on filtering in views alone.

## Phase 3 settings

Profile: display name, email, password; confirm current password for sensitive changes, invalidate/reverify email when changed and rotate sessions appropriately. Workspace: display name, default currency and timezone. Define currency precision and whether currency may change after financial transactions before allowing a change. Provide validation, authorization and feature tests.

## Phase 7 release hardening

Use Laravel-supported password reset and email verification, database-backed session controls if active-session management is implemented, secure cookies/HTTPS, account lockout/rate limits and documented recovery behavior. Add abuse, cross-owner and security regression tests. Optional 2FA remains outside MVP unless a security review requires it.

## Post-MVP collaboration

If small businesses need shared access, introduce `workspace_members` and `workspace_invitations` with workspace-specific roles and explicit authorization tests. Do not infer authorization from a global user role. Decide invitation expiry, revocation, owner transfer and audit retention before implementation.

## Test account guidance

See [10-test-accounts.md](10-test-accounts.md) for example-only manual identities, safe setup and cross-owner acceptance tests. No real credentials are stored in documentation.
