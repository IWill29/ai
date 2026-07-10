<?php

declare(strict_types=1);

namespace App\Domains\Stores\Jobs;

use App\Domains\Stores\Adapters\Shopify\ShopifyClient;
use App\Domains\Stores\Models\StoreConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class RegisterShopifyWebhooksJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public string $storeConnectionId) {}

    public function handle(): void
    {
        $connection = StoreConnection::query()
            ->with('credential')
            ->findOrFail($this->storeConnectionId);

        $credential = $connection->credential;

        if ($credential === null || $credential->access_token === '') {
            return;
        }

        $secrets = $credential->secrets;
        $apiSecret = is_array($secrets) ? ($secrets['api_secret'] ?? null) : null;

        if (! is_string($apiSecret) || $apiSecret === '') {
            $this->markPending($connection, config('shopify.webhook_topics', []));

            return;
        }

        $client = new ShopifyClient(
            $connection->domain,
            $credential->access_token,
            $connection->id,
        );

        $callbackUrl = rtrim((string) config('shopify.webhook_url'), '/')."/{$connection->id}";
        $missing = [];

        foreach (config('shopify.webhook_topics', []) as $topic) {
            try {
                $data = $client->graphql(
                    'mutation($topic: WebhookSubscriptionTopic!, $callbackUrl: URL!) {
                      webhookSubscriptionCreate(
                        topic: $topic,
                        webhookSubscription: { callbackUrl: $callbackUrl, format: JSON }
                      ) {
                        userErrors { message }
                      }
                    }',
                    ['topic' => $topic, 'callbackUrl' => $callbackUrl],
                );

                $userErrors = $data['webhookSubscriptionCreate']['userErrors'] ?? [];

                if ($userErrors !== []) {
                    $missing[] = $topic;
                }
            } catch (Throwable) {
                $missing[] = $topic;
            }
        }

        if ($missing !== []) {
            $this->markPending($connection, $missing);

            return;
        }

        $meta = is_array($connection->meta) ? $connection->meta : [];
        $meta['webhooks'] = [
            'state' => 'registered',
            'registered_at' => now()->toIso8601String(),
            'missing_topics' => [],
            'last_attempt_at' => now()->toIso8601String(),
        ];

        $connection->update([
            'status' => $connection->status === 'webhooks_pending' ? 'active' : $connection->status,
            'meta' => $meta,
        ]);
    }

    /**
     * @param  array<int, string>  $missing
     */
    private function markPending(StoreConnection $connection, array $missing): void
    {
        $meta = is_array($connection->meta) ? $connection->meta : [];
        $meta['webhooks'] = [
            'state' => 'pending',
            'missing_topics' => $missing,
            'last_attempt_at' => now()->toIso8601String(),
        ];

        $connection->update([
            'status' => 'webhooks_pending',
            'meta' => $meta,
        ]);
    }
}
