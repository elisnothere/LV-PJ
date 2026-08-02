# Repository Guidelines

## Project Structure & Module Organization
This is a Laravel 13 storefront application. Core backend code lives in `app/`, with HTTP controllers under `app/Http/Controllers`, Eloquent models in `app/Models`, console commands in `app/Console/Commands`, and integration logic in `app/Services`. Blade templates are in `resources/views`, frontend entry files in `resources/js` and `resources/css`, and public assets in `public/`. Database migrations and seeders live in `database/migrations` and `database/seeders`. Tests are split into `tests/Feature` and `tests/Unit`.

## Build, Test, and Development Commands
- `composer install` — install PHP dependencies.
- `npm install` — install frontend tooling.
- `php artisan migrate --seed` — create schema and seed baseline data.
- `php artisan storage:link` — expose uploaded/imported files from `storage/app/public`.
- `composer run dev` — start the local Laravel server, queue listener, and Vite dev server together.
- `php artisan test` — run the full test suite.
- `php artisan test tests/Feature/ImportProductsFromApiTest.php` — run a focused feature test file.
- `php artisan products:import-api` — import external products and download their images locally.

## Coding Style & Naming Conventions
Follow existing Laravel conventions: PSR-12 formatting, 4-space indentation, one class per file, and descriptive method names. Use singular model names (`Product`) and plural database tables (`products`). Keep controller actions thin; put reusable or integration-heavy logic in `app/Services`. Prefer ASCII text in code and comments. Use `./vendor/bin/pint` before finalizing changes when formatting is needed.

## Testing Guidelines
This repo uses Pest with Laravel integration. Put browser/request and command behavior in `tests/Feature`; reserve `tests/Unit` for isolated logic. Name tests by behavior, for example `it('imports new products and downloads their images')`. For DB-backed feature tests, use `RefreshDatabase`. Fake external HTTP calls and storage in tests instead of hitting real network or filesystem state.

## Commit & Pull Request Guidelines
Recent history mixes styles, but the clearest pattern is short, imperative commits with prefixes such as `feat:` and `fix:`. Prefer messages like `feat: import products from external API`. Keep each commit scoped to one logical change. Pull requests should include: a short summary, affected routes/commands, migration notes, test coverage, and screenshots for UI changes such as cart, catalog, or auth pages.

## Security & Configuration Tips
Do not commit `.env` changes or secrets. For local setup, confirm `APP_URL` matches your dev URL if imported images must resolve through `/storage/...`. When working with imports or uploads, store files through Laravel `Storage` rather than writing directly into `public/`.
