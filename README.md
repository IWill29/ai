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

**Docker app (recommended on Windows):**

```bash
docker compose up -d
cp .env.example .env   # first time only
php artisan key:generate
php artisan migrate
npm run dev            # Vite HMR only — do not run `composer run dev` or `php artisan serve` on the host
```

- App: http://localhost:8000 (Docker `app` service)
- Mailpit: http://localhost:8026

**Host PHP app (alternative):** start only infra, then Laravel dev:

```bash
docker compose up -d pgsql redis mailpit
composer run dev
```

Do not run both Docker `app` and `composer run dev` — they both bind port 8000 and use different database hosts.

## License

Proprietary — All Rights Reserved. See [LICENSE](LICENSE) for terms.
Unauthorized copying, modification, or distribution is prohibited.
