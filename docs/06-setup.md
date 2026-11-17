# Local setup, demo and integration

## Requirements
PHP >= 8.3 compatible with Laravel 13, Composer 2, Node.js/npm compatible with Vite, and SQLite (simplest) or MySQL. PHP extensions: see Composer platform checks and Laravel installation requirements.

## Fresh checkout (Windows PowerShell)
```powershell
git clone https://github.com/ariffasyraf-11/tcg-inventory.git
cd tcg-inventory
composer install
Copy-Item .env.example .env
php artisan key:generate
# Set DB_CONNECTION=sqlite in .env; create database/database.sqlite if missing:
New-Item -ItemType File -Path database/database.sqlite -Force
php artisan migrate
npm install
npm run build
php artisan serve
```

## Fresh checkout (Linux/macOS)
```bash
git clone https://github.com/ariffasyraf-11/tcg-inventory.git
cd tcg-inventory
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
# Set DB_CONNECTION=sqlite in .env
php artisan migrate
npm install
npm run build
php artisan serve
```

Open http://127.0.0.1:8000/demo after the local prototype is present. The `/demo` route should return 404 when `APP_ENV` is not `local` or `testing`, even if route cache is used.

## Livewire
Livewire 4 production integration is a separate milestone: run `composer require livewire/livewire:^4.0`, confirm version compatibility and build a real authenticated owner workspace. Do not install Filament.

## Verification
```bash
php artisan --version
composer validate
php artisan test
./vendor/bin/pint --test
npm run build
```

## Merge into a different clean Laravel project
Transfer only custom TCG application files (models, actions, views, components, migrations, routes, tests, seeders). Merge routes and dependency/config changes explicitly. Never overwrite `bootstrap/`, `config/`, `composer.json`, `package.json` or existing migrations without a reviewed diff. Test on a fresh database. No `migrate:fresh` against production.

## Production
`APP_ENV=production`, `APP_DEBUG=false`, HTTPS, secure sessions, backups and secret management. Never expose local demo write/reset routes or shared demo credentials on production.

## Authenticated catalog (main)

On `main`, run migrations and open `/register`, then `/catalog`. No default seller credentials are created. The catalog is not yet Livewire-based. Production stock operations are not yet implemented.


## Test identities

See [10-test-accounts.md](10-test-accounts.md). No test users or passwords are provisioned by default; register example-only identities locally and do not use shared production credentials.
