# URL Shortener

Minimal URL shortener scaffold based on PHP, Redis, and SQLite.

## Structure

- `api/` — PHP API plus service layer
- `redirect.php` — short URL redirect entrypoint
- `docker-compose.yml` — app + Redis services
- `frontend/` — Vue 3 starter dashboard
- `data/` — SQLite database file location

## Quick Start

1. Build the PHP app image and start Redis + PHP with Docker:

   docker compose up --build -d

2. Initialize the database:

   docker compose exec app php api/init_db.php

3. Start the frontend (optional):

   cd frontend && npm install && npm run dev

4. Open the app server:

   http://localhost:8000

5. Open the Vue frontend:

   http://localhost:5173

## API Endpoints

- `POST /api/links` — create a short link
- `GET /api/links` — list saved links
- `GET /api/links/{code}` — get a single link
- `DELETE /api/links/{code}` — delete a link
- `GET /{code}` — redirect to the original URL

## Notes

- The backend uses SQLite for local persistence and Redis for fast redirect caching.
- The Vue frontend is scaffolded under `frontend/` and can be extended with Tailwind and charts.
