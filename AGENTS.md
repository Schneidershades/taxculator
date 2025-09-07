# Repository Guidelines

## Project Structure & Module Organization
- `app/`: Core domain and application code. Admin UI lives under `app/Filament`.
- `routes/`: API endpoints in `routes/api.php` (versioned under `v1`); web routes in `routes/web.php`.
- `database/`: Migrations, seeders, factories.
- `resources/`: Views/assets; API doc resources under `resources/scribe`.
- `config/`: App configuration, including `config/scribe.php`.
- `public/`: Web root for built assets and docs.
- `tests/`: Pest tests in `tests/Feature` and `tests/Unit`.

## Build, Test, and Development Commands
- Install: `composer install && npm install`
- Configure: `cp .env.example .env && php artisan key:generate`
- Migrate DB: `php artisan migrate`
- Dev loop (server, queue, logs, assets): `composer dev`
- Serve only: `php artisan serve`
- Assets: `npm run dev` (watch) / `npm run prod` (minify)
- Tests: `composer test` or `php artisan test --parallel`
- API docs: `php artisan scribe:generate`

## Coding Style & Naming Conventions
- PHP style: Laravel preset via Pint/StyleCI. Format with `vendor/bin/pint`.
- Indentation: 4 spaces (YAML: 2). LF line endings (.editorconfig).
- Naming: Classes StudlyCase; methods camelCase; migrations snake_case timestamps.
- Structure: Controllers under `App\Http\Controllers\Api\V1\...`; tests end with `*Test.php`.

## Testing Guidelines
- Framework: Pest on top of Laravel’s test runner.
- Location: Feature tests in `tests/Feature/...`; unit tests in `tests/Unit/...`.
- Database: In-memory SQLite (see `phpunit.xml`). Use `RefreshDatabase` for stateful tests.
- Run subsets: `php artisan test --filter TaxApiTest` or `php artisan test --parallel`.

## Commit & Pull Request Guidelines
- Commits: Short, imperative summaries; add scope when helpful (e.g., `tax: compute NHF override`).
- PRs must include: clear description, linked issues, setup/validation steps, and JSON request/response samples for API changes; screenshots for Filament UI updates.
- Ensure before review: tests pass, code formatted (Pint), and API docs regenerated (`php artisan scribe:generate`) when routes/responses change.

## Security & Configuration Tips
- Never commit `.env` or secrets. Configure `APP_URL`, DB, cache/queue drivers locally.
- Auth: Use Sanctum where required; respect `throttle:tax-calc` limits for new endpoints.
