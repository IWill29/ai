<?php

declare(strict_types=1);

return [
    'plans' => [
        [
            'slug' => 'free',
            'name' => 'Free',
            'price_cents' => 0,
            'currency' => 'EUR',
            'store_limit' => 1,
            'monthly_message_limit' => 100,
        ],
        [
            'slug' => 'pro',
            'name' => 'Pro',
            'price_cents' => 1900,
            'currency' => 'EUR',
            'store_limit' => 3,
            'monthly_message_limit' => 1000,
        ],
        [
            'slug' => 'business',
            'name' => 'Business',
            'price_cents' => 4900,
            'currency' => 'EUR',
            'store_limit' => null,
            'monthly_message_limit' => null,
        ],
    ],

    'agent_steps' => [
        [
            'title' => 'Connect & sync Shopify',
            'body' => 'Create a custom app, paste your Admin API token, and AgentStore mirrors orders, products, and customers. Webhooks plus nightly reconcile keep the mirror fresh.',
            'tools' => [],
            'kind' => 'setup',
        ],
        [
            'title' => 'Ask in plain English',
            'body' => 'Type what you need — “Show unfulfilled orders from last week” or “Update stock for SKU-12”. The agent plans the next steps from your message.',
            'tools' => [],
            'kind' => 'chat',
        ],
        [
            'title' => 'Reads from your mirror',
            'body' => 'Lookups run against your synced PostgreSQL data — fast and consistent. No live Shopify call on every chat turn.',
            'tools' => [
                'list_orders',
                'get_order',
                'get_metrics',
                'list_products',
                'get_product',
                'list_customers',
            ],
            'kind' => 'read',
        ],
        [
            'title' => 'Live action trace',
            'body' => 'Every tool call appears in the chat thread — which orders were searched, which products matched, and what the agent found before it answers.',
            'tools' => [],
            'kind' => 'trace',
        ],
        [
            'title' => 'Confirm before every write',
            'body' => 'Fulfill, refund, update inventory, or edit catalog entries only after you tap Confirm. Full audit trail for every change.',
            'tools' => [
                'fulfill_order',
                'update_inventory',
                'update_product',
                'refund_order',
                'cancel_order',
                'tag_order',
            ],
            'kind' => 'write',
        ],
    ],

    'faqs' => [
        [
            'q' => 'What is BYOK (Bring Your Own Key)?',
            'a' => 'You add your own OpenRouter API key in settings. The AI agent uses your key for chat — usage is billed by OpenRouter, not AgentStore. Our subscription covers the platform: sync, dashboard, chat UI, and agent tools.',
        ],
        [
            'q' => 'How do I connect Shopify?',
            'a' => 'Create a custom app in your Shopify admin, enable the required Admin API scopes, and paste the access token in AgentStore. We keep a full mirror of orders, products, and customers.',
        ],
        [
            'q' => 'Is my store data secure?',
            'a' => 'API tokens are encrypted at rest. We never log secrets. Data is scoped to your account only. You can delete your account anytime — we purge credentials and synced data.',
        ],
        [
            'q' => 'Does the agent change my store without asking?',
            'a' => 'Read operations run automatically. Every write requires your explicit Confirm in the chat UI, with a full audit trail.',
        ],
        [
            'q' => 'Which AI models can I use?',
            'a' => 'Any model from our OpenRouter allow-list. You pick the model per message in chat. GPT-4o mini is a sensible default.',
        ],
    ],
];
