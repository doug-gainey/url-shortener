# AGENTS.md

URL shortener built with PHP 8.2 (REST API), Vue 3 + TypeScript (SPA frontend),
Redis (cache + click counters), and SQLite (persistent store). Docker Compose
for local dev; `redirect.php` is the hot path — treat it carefully.

---

## Commands

### Backend (PHP)

```bash
# Build the app image and start services (or rebuild after Dockerfile changes)
docker compose up --build -d

# Initialize the SQLite database schema (or update after schema changes)
docker compose exec app php api/init_db.php

# View logs
docker compose logs -f

# Stop services
docker compose down
```

### Frontend (Vue)

```bash
cd frontend

# Install dependencies
npm install

# Run development server
npm run dev

# Build production assets
npm run build

# Preview the production build
npm run preview
```

---

## Verification

The current codebase does not include automated PHP or frontend test suites yet.

Before marking a task complete:

1. Confirm the backend responds at `http://localhost:8000/api/links`.
2. Confirm the frontend loads at `http://localhost:5173` or the Vite-assigned port.
3. If a task touches `redirect.php`, manually verify a redirect round-trip:
   `curl -I http://localhost/{code}` and confirm a `302` response.

---

## Architecture & Conventions

### Request Flow

```
Browser → Vue SPA (/dashboard) → PHP REST API (/api/*)
Browser → redirect.php (/:code) → Redis lookup → 302 redirect
```

### Redis Key Schema

```
url:{code}          → original URL string (TTL: 86400s)
ratelimit:{hash}    → request count (TTL: 60s)
```

Current implementation uses Redis only for caching URL resolutions and rate limiting. Click counters are stored directly in SQLite via `Link::incrementClicks()`.

Never deviate from this schema without updating `RedisService.php` and this file.

### API Response Envelope

Current controllers return JSON responses with `data` and `error` fields.

Example success response:

```json
{
  "data": {...},
  "error": null
}
```

Example failure response:

```json
{
  "data": null,
  "error": "..."
}
```

### Short Codes

- Generated with a random base62-safe alphabet.
- Minimum length is 6 characters.
- Reserved codes that should not be accepted as custom aliases:
  `api`, `admin`, `static`, `login`, `dashboard`, `health`, `favicon.ico`

### HTTP Redirects

- Always use **302** (not 301) — 301s get cached by browsers permanently
- Redirect happens in `redirect.php`, not in the API controllers

### Click Tracking

- Current code increments click counts directly in SQLite via `Link::incrementClicks()`.
- Redis is only used for URL caching in `redirect.php`.

### Analytics Tracking

- **Analytics Schema** — Separate `analytics` table tracks:
  - `short_code` — Link identifier
  - `user_agent` — Browser/client user agent string
  - `referrer` — HTTP Referer header
  - `ip_hash` — SHA256 hash of client IP (privacy-preserving, no raw IPs stored)
  - `created_at` — Timestamp of the click
- **Recording Flow** — Each redirect in `redirect.php`:
  1. Increments click counter via `Link::incrementClicks()`
  2. Records analytics via `Analytics::recordClick(code, userAgent, referrer, ipHash)`
- **API Endpoints**:
  - `GET /api/links/{code}/analytics` — Paginated click analytics (limit, offset params)
  - `GET /api/links/{code}/analytics/summary` — Summary stats (total_clicks, unique_ips, unique_agents)
- **Indexes** — Created on: `short_code`, `created_at`, `ip_hash` for efficient queries
- **Never** log raw IP addresses; always hash them with `hash('sha256', $ip . $salt)`

### Link Lifecycle & Deletion

- **Soft Delete (Deactivate)**: `DELETE /api/links/{code}` sets `is_active = 0` without removing the link from the database
  - Deactivated links return `410 Link deactivated.` when accessed
  - Deactivated links can be restored with `PUT /api/links/{code}` with `is_active: true`
  - Original URL is cleared from Redis cache on deactivation
- **Permanent Delete**: `DELETE /api/links/{code}?permanent=1` only works if `is_active = 0`
  - Returns `422` error if attempting to permanently delete an active link
  - Permanently deleted links are removed from the database completely
  - Cannot be undone; frontend shows confirmation dialog

### URL Validation

- Accept only `http://` and `https://` schemes — reject everything else including
  `javascript:`, `data:`, `ftp:`, `mailto:`
- Normalize before storing: lowercase scheme and host, strip default ports

### Rate Limiting

- 10 requests per minute per client IP (hashed for privacy)
- Applied to POST /api/links endpoint
- Uses Redis for distributed rate limiting

### Logging

- All API requests logged to `logs/app.log`
- Client IPs hashed for privacy
- Log levels: INFO, WARNING, ERROR
- Structured JSON context in logs

### Database Schema Management

- **Schema initialization** happens in `api/init_db.php` only — run with:
  ```bash
  docker compose exec app php api/init_db.php
  ```
- **Never** add schema creation code to `bootstrap.php`; it loads on every request
- **For new schema changes**:
  1. Add migration logic to the appropriate model's `ensureSchema()` method (e.g., `Link::ensureSchema()`, `Analytics::ensureSchema()`)
  2. Use idempotent checks: `CREATE TABLE IF NOT EXISTS`, `CREATE INDEX IF NOT EXISTS`
  3. For new columns: check if column exists before adding with SQLite `PRAGMA table_info()`
  4. Run `docker compose exec app php api/init_db.php` to apply changes
- **Data safety**: `init_db.php` does not drop or truncate tables — all schema changes are additive and preserve existing data
- **bootstrap.php role**: Runtime wiring and service initialization only (no schema creation)

---

## File Structure

```
api/
  bootstrap.php          # App bootstrap, config loading, service wiring
  index.php              # API router entry point
  init_db.php            # SQLite schema creation script
  config/config.php      # DB, Redis, app config
  controllers/           # One class per file, suffix: Controller
    LinkController.php
    AnalyticsController.php
    ConfigController.php
  models/                # One class per file
    Link.php
    Analytics.php
  services/              # RedisService.php, ShortCodeGenerator.php, UrlValidator.php, RateLimiter.php, Logger.php
  error_page.php         # Styled error page function

frontend/
  package.json
  tsconfig.json
  vite.config.ts
  src/
    App.vue
    main.ts
    api/                 # API client code
    components/          # Reusable UI components
    router/              # Vue Router setup
    stores/              # Pinia state stores
    views/               # Page-level views

redirect.php             # Hot path — resolve short codes and redirect
router.php               # Local PHP router for development
logs/                    # Application logs (created automatically)
data/                    # SQLite database files
docker-compose.yml
Dockerfile
```

---

## Constraints

- **Never** commit `.env` or any file containing secrets
- **Never** use `301` redirects — only `302`
- **Never** write click counts synchronously to SQLite per-request
- **Never** run database migrations against a production connection string
- **Never** introduce a new Redis key pattern without updating the schema above
- **Do not** modify `redirect.php` for non-redirect concerns — it must stay lean
- **Do not** add `console.log` or `var_dump` / `print_r` debug calls in committed code
- **Do not** store raw IP addresses — hash them with `hash('sha256', $ip . $salt)`
  before persisting to SQLite or logging

---

## Common Gotchas

- Redis must have AOF or RDB persistence enabled in `docker-compose.yml` — without
  it all short codes vanish on container restart
- Custom aliases must be validated: alphanumeric and hyphens only, 3–64 chars,
  not in the reserved codes list
- Expired links (past `expires_at`) must return a friendly HTML page, not a raw 404
- The frontend currently uses a hardcoded PHP API base URL and does not rely on a Vite proxy.
- There is no Nginx config in this repo; development traffic is served by PHP's built-in server.
- Rate limiting uses hashed IPs; ensure consistent hashing across requests
- Database indexes are critical for performance; always recreate after schema changes
