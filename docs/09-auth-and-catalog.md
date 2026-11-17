# Authenticated catalog foundation

## Routes
- `GET/POST /register` and `GET/POST /login` use Laravel's built-in session authentication.
- `POST /logout` invalidates the session and rotates CSRF token.
- `GET/POST /catalog` requires an authenticated user and scopes every product query to their one owner workspace.
- `GET /demo` remains a separate local-only session prototype.

## MVP implementation
Register a seller, then open `/catalog` to create a single or sealed product. Each product receives an initial SKU variant. Product data is isolated by owner workspace; the demo workspace is read-only for the catalog endpoint. This is **catalog only**; quantity and costing are deliberately not implemented here.

## Before production
Add rate limiting to authentication, email verification/password recovery, privacy and retention policies, user-facing account management, more robust SKU uniqueness at database level, and production deployment checks. Review Laravel starter kits before replacing the minimal auth UI. No shared credentials.

## Follow-up
Install Livewire 4 with Composer and a committed lockfile, then migrate catalog and inventory screens to Livewire while retaining the controller/service boundaries. Implement stock batches, ledger, receipts and adjustments in a separate PR with transactional tests.
