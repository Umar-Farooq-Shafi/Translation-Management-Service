# Translation Management Service (Laravel)

A high-performance, token-secured translation API with tagging, search, and JSON export.

## Features
- Translations across locales with unique (key, locale)
- Tags for context (mobile, desktop, web, etc.)
- Filter by tags, key, locale, or content
- JSON export endpoint with ETag + CDN-friendly Cache-Control
- Sub-200ms endpoints target; export target < 500ms with large datasets
- 100k+ seed via `translations:seed`
- Custom Bearer token auth (no external auth libs)
- OpenAPI spec (`public/openapi.yaml`)
- Dockerized stack (Nginx, PHP-FPM, MySQL, Redis)
- Tests (unit/feature) incl. basic performance checks

## Quick Start
1. Ensure Docker is installed.
2. Copy a fresh Laravel app or place these files into a Laravel 10/11 project.
3. `docker compose up -d`
4. Run migrations and seed:
   - `docker compose exec app php artisan migrate`
   - `docker compose exec app php artisan token:create "admin"` -> copy the printed token
   - (optional) `docker compose exec app php artisan translations:seed 100000`
5. Use the token:
   - Set header `Authorization: Bearer {your_token}` on requests.

## API
- `GET /api/translations` list with filters (`locale`, `key`, `q`, `tags[]`)
- `POST /api/translations` upsert
- `GET /api/translations/{id}` show
- `PUT/PATCH /api/translations/{id}` update
- `DELETE /api/translations/{id}` delete
- `GET /api/export?locale=en&tags[]=web` -> `{ "home.title": "Welcome", ... }`
  - Returns `ETag` header; supports `If-None-Match`

## Design Notes
- PSR-12 and SOLID: controllers lean, export logic encapsulated in a service, resources for shape.
- Performance: indexed columns, eager loading, cache memoization with short TTL, ETag versioning.
- Security: custom token middleware with sha256 token hashing and last-used tracking.
