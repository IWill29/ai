<?php

declare(strict_types=1);

return [
    'data_processing_note' => [
        'title' => 'Data processing for merchants',
        'updated_at' => '2026-07-14',
        'sections' => [
            [
                'heading' => 'Who processes your data',
                'body' => 'AgentStore acts as a processor when you connect a store and use the AI agent. You remain the controller of your customers\' and store data. We process data only to provide the service you request.',
            ],
            [
                'heading' => 'What we process',
                'body' => 'Store credentials (encrypted at rest), mirrored orders/products/customers, chat messages, agent memories, usage metering, and audit logs of confirmed write actions. OpenRouter API keys are stored encrypted per account (BYOK).',
            ],
            [
                'heading' => 'Why we process it',
                'body' => 'To sync your store, answer agent questions, execute confirmed write actions, validate API keys, and maintain security audit trails. We do not sell merchant or customer data.',
            ],
            [
                'heading' => 'Where data is stored',
                'body' => 'Application data is stored in your deployment region (PostgreSQL, Redis). Temporary chat attachments are stored in tenant-scoped private storage and expire automatically.',
            ],
            [
                'heading' => 'Retention and deletion',
                'body' => 'Disconnecting a store purges credentials and mirrored data for that store. Deleting your account cancels billing and permanently purges credentials, synced data, conversations, memories, attachments, and audit logs for your account.',
            ],
            [
                'heading' => 'Sub-processors',
                'body' => 'Depending on your configuration: OpenRouter (AI inference, your key), Shopify (store API), Stripe (billing, when enabled), and your email provider for transactional messages.',
            ],
            [
                'heading' => 'Your rights',
                'body' => 'You can export mirrored store data from your commerce platform at any time. Use Profile settings to delete your account and trigger a full data purge. Contact support for data subject requests relating to your merchant account.',
            ],
        ],
    ],
];
