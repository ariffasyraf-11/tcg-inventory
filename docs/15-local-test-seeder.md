# Local test-data seeder

For local development only, run the **opt-in** `LocalInventoryExampleSeeder` to create two isolated seller accounts, sample singles and sealed products, receipt batches, and movement history through the real transactional inventory service.

1. Confirm `APP_ENV=local` and that the database is disposable.
2. Set `LOCAL_DEMO_PASSWORD` in your uncommitted local `.env` to a unique password of at least 12 characters. Never commit or share it.
3. Run `php artisan migrate`.
4. Run `php artisan db:seed --class=LocalInventoryExampleSeeder`.
5. Sign in at `/login` using `seller-a@example.test` or `seller-b@example.test` and your chosen local password. Compare their private catalog, stock and settings.

**Seeded example:** Seller A has Sample Dragon (12) and Starter Deck (8); Seller B has Sample Mage (4) and Booster Box (2). Re-running the seeder will not duplicate existing inventory receipts. Existing accounts retain their current passwords; the seeder does not reset them.

The default `DatabaseSeeder` is not modified. The seed command is rejected outside local/testing. The addresses use the reserved `.test` domain and cannot receive real email. The authenticated persistent sample demo at `/demo/database` remains separate from these real-workflow test fixtures.
