# Test accounts and workspace isolation

## Purpose and safety

This document describes **test account scenarios**, not live credentials. No test accounts, passwords, or seeded users are guaranteed to exist on a fresh checkout. Never commit real passwords, tokens, personal email addresses, production data or a shared demo login to this repository.

The application currently provides self-registration at `/register`, login at `/login`, and an authenticated catalog at `/catalog`. Each new account gets its own private workspace when visiting the catalog. The local-only `/demo` is a separate session prototype and is **not** an authenticated demo user.

## Suggested manual test identities

Use example-only addresses on a local database. Register these accounts yourself through the UI; choose unique strong passwords stored outside the repository.

| Alias | Example email (local testing only) | Workspace | Purpose |
|---|---|---|---|
| Seller A | `seller-a@example.test` | Seller A Workspace | Normal catalog and future stock workflows |
| Seller B | `seller-b@example.test` | Seller B Workspace | Verify owner isolation and unauthorized access |
| Fresh Seller | `fresh-seller@example.test` | Fresh Seller Workspace | Empty state and first-use experience |

These are **illustrative identities**, not provisioned accounts or credentials. The reserved `example.test` domain does not receive email; do not use it to test password reset or verification email delivery. For mail workflows use a controlled mail testing service and separate throwaway addresses.

## Manual acceptance walkthrough

1. Run migrations against a disposable local database; start the app following [06-setup.md](06-setup.md).
2. Register Seller A, visit `/catalog`, create a fictional single and a sealed product, and log out.
3. Register Seller B, visit `/catalog`, and verify Seller A's products are absent.
4. Attempt access to another workspace's records by manipulating request IDs where applicable; expect rejection, not just hidden UI.
5. Register Fresh Seller and confirm a clean catalog and a private workspace.
6. Verify logout blocks access to `/catalog` and redirects guests to login.
7. When Phase 2 is implemented, repeat cross-owner checks for receiving, adjusting, movements, batches and inventory IDs.
8. When Phase 3 is implemented, verify profile changes affect only the signed-in user and workspace settings affect only their workspace.
9. When Phase 7 is implemented, test password reset, verification, session revocation and rate limiting with controlled mail delivery.

## Automated tests

Use Laravel factories and `RefreshDatabase` to create independent users and workspaces; do not rely on hard-coded IDs or manually registered accounts. Test guest rejection, owner-only reads and writes, cross-workspace mutation rejection, stock actor attribution, negative stock prevention and transaction rollback. Later add password-reset and email-verification tests with fake mail/notification delivery.

## Demo and reset boundaries

The existing `/demo` route uses fictional session-scoped inventory, works only in local/testing environments and does not create or authenticate a seller. Future database-backed demo fixtures must be isolated, disposable, clearly labeled and reset only through authorized actions. Never run destructive database refreshes against production.

## Cleanup

Test accounts are disposable only when created in a disposable local/test database. To reset a local test database, first verify the active environment and database connection; `php artisan migrate:fresh` destroys all tables and must **never** run against a real or shared database.

## Optional local seed fixtures
See [15-local-test-seeder.md](15-local-test-seeder.md). Two test identities and stock fixtures can now be created by an explicit local-only seeder with a user-supplied password. This does not create any default production credentials.
