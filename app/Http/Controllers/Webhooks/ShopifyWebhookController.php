<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Domains\Stores\Jobs\ProcessWebhookEventJob;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\WebhookEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ShopifyWebhookController extends Controller
{
    public function handle(Request $request, string $storeConnectionId): Response
    {
        /** @var StoreConnection $connection */
        $connection = $request->attributes->get('shopify_store_connection')
            ?? StoreConnection::query()->findOrFail($storeConnectionId);

        $eventId = (string) $request->header('X-Shopify-Webhook-Id', '');
        $topic = (string) $request->header('X-Shopify-Topic', '');

        if ($eventId === '' || $topic === '') {
            abort(400, 'Missing webhook headers');
        }

        $event = WebhookEvent::query()->firstOrCreate(
            [
                'store_connection_id' => $connection->id,
                'external_event_id' => $eventId,
            ],
            [
                'platform' => 'shopify',
                'topic' => $topic,
                'status' => 'received',
                'payload' => $request->all(),
            ],
        );

        if ($event->wasRecentlyCreated) {
            ProcessWebhookEventJob::dispatch($event->id)->afterCommit();
        }

        return response('OK', 200);
    }
}
