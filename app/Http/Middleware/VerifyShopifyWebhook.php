<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Stores\Models\StoreConnection;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyShopifyWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $storeConnectionId = $request->route('storeConnectionId');

        if (! is_string($storeConnectionId) || $storeConnectionId === '') {
            abort(404);
        }

        $connection = StoreConnection::query()
            ->with('credential')
            ->findOrFail($storeConnectionId);

        $secret = $connection->credential?->secrets;
        $apiSecret = is_array($secret) ? ($secret['api_secret'] ?? null) : null;

        if (! is_string($apiSecret) || $apiSecret === '') {
            abort(401, 'Webhook secret not configured');
        }

        $hmac = (string) $request->header('X-Shopify-Hmac-Sha256', '');
        $computed = base64_encode(hash_hmac('sha256', $request->getContent(), $apiSecret, true));

        if ($hmac === '' || ! hash_equals($hmac, $computed)) {
            abort(401, 'Invalid webhook signature');
        }

        $request->attributes->set('shopify_store_connection', $connection);

        return $next($request);
    }
}
