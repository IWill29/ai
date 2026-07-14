# AgentStore

AI-powered e-commerce operations platform for merchants.

Connect your Shopify store via API keys and manage orders, products, and customers through a natural-language AI agent — with a dashboard, chat history, and visual action trace.

## Stack

- Laravel 13 + React 19 + Inertia 2
- PostgreSQL + pgvector
- Redis
- OpenRouter (BYOK)
- Shopify Admin API (v1)

## Local development

### Prerequisites

- **Docker Desktop** — must be **running** before `docker compose` (the `dockerDesktopLinuxEngine` pipe error means it is stopped or not installed).
- **Node.js** — for `npm run dev` (Vite HMR on the host).
- **PHP 8.4 + Composer** (optional on Windows) — only if you run `php artisan` on the host; the Docker workflow below runs Artisan inside the container.

### Docker app (recommended on Windows)

```powershell
# 1. Start Docker Desktop, then:
docker compose up -d --build

# 2. First-time env (PowerShell)
copy .env.example .env

# 3. PHP dependencies — required because the project volume hides vendor/ from the image
docker compose run --rm app composer install

# 4. Laravel setup (inside the container)
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate

# 5. Frontend HMR on the host (Wayfinder runs via Docker on Windows — see vite.config.ts)
npm install
npm run dev
```

- App: http://localhost:8000 (Docker `app` service)
- Mailpit: http://localhost:8026

Do not run both Docker `app` and `composer run dev` — they both bind port 8000 and use different database hosts.

### Host PHP app (alternative)

Start only infra, then Laravel dev on the host:

```bash
docker compose up -d pgsql redis mailpit
composer install
composer run dev
```

## License

Proprietary — All Rights Reserved. See [LICENSE](LICENSE) for terms.
Unauthorized copying, modification, or distribution is prohibited.
