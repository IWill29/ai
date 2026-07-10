<?php

declare(strict_types=1);

return [
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
