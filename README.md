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

```bash
docker compose up -d
cp .env.example .env
php artisan key:generate
php artisan migrate
composer run dev
```

- App: http://localhost:8000
- Mailpit: http://localhost:8025

## License

Proprietary — All Rights Reserved. See [LICENSE](LICENSE) for terms.
Unauthorized copying, modification, or distribution is prohibited.
