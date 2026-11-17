# TCG Inventory

A demo-first inventory application for individual trading-card sellers and small businesses.

**Stack:** Laravel 13, Livewire 4 (planned for production inventory screens), Blade, Tailwind, custom UI. **No Filament.**

## Current status

- ✅ Fresh official Laravel 13 application skeleton on `feature/fresh-laravel-foundation`
- ✅ Development/build and demo-first specifications
- ⏳ Install dependencies and validate a clean local boot
- ✅ Local-only interactive session UX demo at `/demo` (not yet dependency-tested)
- ⏳ Production inventory models, transactions, authentication and stock workflows

The local UX prototype is **not** a production stock management service. It must not be used to record actual inventory.

## Local setup

See [docs/06-setup.md](docs/06-setup.md) for Windows and Linux commands.

## Demo acceptance goal

Open `/demo` in local development; receive five units of Sample Dragon, adjust one unit out with a reason, inspect movement history, and reset to the original twelve units. Later phases will replace the session prototype with persisted inventory services and isolated owner/demo workspaces.

## Documentation

- [MVP scope](docs/01-scope.md)
- [Architecture](docs/02-architecture.md)
- [Data model](docs/03-data-model.md)
- [Demo UX](docs/04-demo-experience.md)
- [Roadmap](docs/05-roadmap.md)
- [Setup and integration](docs/06-setup.md)
- [Code review](docs/07-code-review.md)
- [Decision log](docs/08-decisions.md)

## Clean Laravel starting point

The framework skeleton is from the official `laravel/laravel` 13.x repository (source tree `a255d7688770af9713daff1439c47ff9bc6abd47`). No application files were transferred from a previous TCG project. Laravel is MIT licensed. No generated vendor/node_modules assets or credentials are committed.
