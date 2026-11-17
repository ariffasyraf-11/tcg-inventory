# Phase 3 owner settings increment

Authenticated owner-only settings at `/settings` now include display-name changes, current-password-protected password changes, and workspace-name updates. Workspace ownership is resolved from the session, not a request parameter. Feature tests cover authentication, password checks, and cross-owner isolation.

Email changes are intentionally disabled until verification and re-verification are implemented. Currency/timezone configuration is deferred until the financial policy is defined; no migration or settings UI is claimed for these fields.

Phase 3 remains **in progress**: Livewire 4 is not installed and the existing stock/settings screens use Blade. See `05-roadmap.md` for the remaining acceptance gates.
