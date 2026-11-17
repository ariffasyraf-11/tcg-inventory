# Phased build roadmap

This roadmap reflects the merged Laravel foundation and authenticated catalog. Each phase is delivered in a focused PR, with CI and review before merge. The currently implemented access model is owner-only. Phase 6.5D plans collaboration without implying that shared access is already available.

| Phase | Deliverable | Exit gate | Status |
|---|---|---|---|
| 0 | Clean Laravel 13 skeleton, setup docs, local session-only demo | Install, tests, demo behavior and CI validated | Complete (PR #1) |
| 1 | Session authentication, one private owner workspace, singles/sealed catalog | Authentication, cross-owner isolation and CI tests pass | Complete (PR #2; logout fix PR #5) |
| 2 | Inventory items, receipt batches, immutable movement ledger, receiving and adjustments | Atomicity, authorization, no-negative-stock, batch reconciliation and rollback tests | Backend merged (PR #4); hardening pending |
| 3 | Livewire 4 integration, stock management screens, basic profile and workspace settings | Livewire dependency locked; authenticated UX and settings tests pass | In progress: Blade stock UI merged (PR #6); owner settings in PR #7; Livewire pending |
| 4 | Isolated database-backed demo workspace and guided stock journey | Demo cannot access or mutate real owner data; reset is authorized | In progress: isolated DB sample demo; domain parity pending |
| 5 | Manual sales and persisted FIFO batch allocations | Deterministic FIFO, correct cost, no overselling and audit tests | Merged (PR #9); further financial hardening pending |
| 6 | Dashboard, low-stock thresholds and CSV import preview/validation | Accurate summaries; no silent partial imports | Dashboard and preview merged (PRs #10–#11); confirmed atomic import merged (PR #13) |
| 6.5 | Seller UX: product images, inventory search/details, optional SKU for singles | Safe media handling; owner-scoped detail pages; SKU-less singles work with inventory while CSV import stays unambiguous | 6.5A/B/C merged (PRs #15–#17). Scope in [21-phase-6-5-ux-and-optional-sku.md](21-phase-6-5-ux-and-optional-sku.md) |
| 6.5D | Workspace and collaboration: membership, invitations, roles, switching and revocation | Owner isolation, explicit role matrix, invite lifecycle, audit actor attribution and authorization tests | D1/D2 merged (PRs #18–#19); D3 catalog edit permissions in development; switching/shared application access pending. See [23-workspace-collaboration-phase.md](23-workspace-collaboration-phase.md) |
| 7 | Release hardening: password recovery, email verification, active sessions, security, backup and deployment | Recovery and verification tests; production checklist and restore rehearsal pass | Planned |


User-facing usage and explicit implemented/planned collaboration boundaries are documented in [22-user-guide-workspaces-and-collaboration.md](22-user-guide-workspaces-and-collaboration.md).

## User management decisions

- **MVP access:** one owner account per workspace. No staff invitations, global administrator UI, branches or subscription roles are implemented yet; workspace collaboration is now scheduled for Phase 6.5D.
- **Workspace model:** one private workspace per account. Retain the owner relationship for V1; design authorization so future workspace membership is possible without migrating stock ownership to users.
- **Profile and workspace settings:** deliver simple name, email and password changes and workspace name, currency and timezone settings in Phase 3; require current password and re-verification for sensitive changes.
- **Security timing:** retain existing login/register throttling; implement password reset, email verification and active-session management before production release in Phase 7. Optional 2FA is a post-MVP enhancement unless risk review changes priority.
- **Audit:** Phase 2 stock actions must record actor identity and enforce workspace ownership. Never delete or rewrite stock movement history during routine user management.
- **Testing:** use independent accounts and owner-isolation fixtures documented in [10-test-accounts.md](10-test-accounts.md). No shared seeded production credentials.

## Phase 2 implementation boundaries

Use integer stock units and decimal-safe costs. Every receipt and adjustment must update on-hand and batch balances together with an append-only movement inside a database transaction. Adjustments out must allocate batch decrements consistently (FIFO is the proposed default) so batch remaining totals reconcile to on-hand. Lock the affected inventory rows and prevent negative balances. The phase does not include a new dashboard or sales UI.

## Current work

PRs #1–#19 are merged into `main`. Phase 3 stock UI and owner settings are implemented with Blade; Livewire 4 remains pending. Phase 4 has an isolated database-backed sample demo; production-domain parity remains pending. Phase 5 manual sales/FIFO is merged. Phase 6 dashboard and low-stock thresholds merged in PR #10; CSV preview merged in PR #11; shared sidebar merged in PR #12; confirmed import merged in PR #13. Phase 6.5A optional SKU and workspace user guide merged in PR #15. Phase 6.5B images merged in PR #16; 6.5C inventory navigation and 6.5D workspace collaboration are active workstreams before Phase 7.
