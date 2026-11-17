# Shared seller workspace navigation

Authenticated application pages now use `resources/views/components/app-shell.blade.php` for a common responsive shell, sidebar navigation, active route indication, account identity and logout form. Navigation is horizontal and scrollable on narrow screens, and a persistent left sidebar on large screens. All links use named Laravel routes and the existing `auth` middleware.

Converted pages: dashboard, catalog, inventory, sales, CSV preview, private database demo and settings. Public welcome, login, registration and local session-only demo are deliberately excluded because they do not represent an authenticated seller workspace. The existing page forms and routes remain unchanged.

## Review checklist

- Navigate to each authenticated page and verify current item highlighting.
- Check desktop sidebar and mobile horizontal navigation; keyboard focus and logout CSRF.
- Confirm forms, errors, pagination, demo reset and all inventory actions still work.
- Verify private pages redirect guests to login and no navigation link crosses workspace ownership.
