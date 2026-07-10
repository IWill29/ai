<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stores;

use App\Domains\Stores\Actions\DisconnectStoreAction;
use App\Domains\Stores\Actions\ReconnectShopifyStoreAction;
use App\Domains\Stores\Exceptions\InvalidCredentialsException;
use App\Domains\Stores\Models\StoreConnection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stores\ReconnectShopifyStoreRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StoreConnection::class);

        /** @var list<array{id: string, name: string, domain: string, platform: string, status: string, lastSyncedAt: string|null}> $stores */
        $stores = StoreConnection::query()
            ->where('account_id', $request->user()->account_id)
            ->orderBy('name')
            ->get()
            ->map(function (StoreConnection $connection): array {
                $lastSyncedAt = $connection->last_synced_at;

                return [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'domain' => $connection->domain,
                    'platform' => $connection->platform,
                    'status' => $connection->status,
                    'lastSyncedAt' => $lastSyncedAt instanceof Carbon
                        ? $lastSyncedAt->toIso8601String()
                        : null,
                ];
            })
            ->values()
            ->all();

        return Inertia::render('stores/index', [
            'stores' => $stores,
        ]);
    }

    public function destroy(
        StoreConnection $storeConnection,
        DisconnectStoreAction $disconnectStore,
    ): RedirectResponse {
        $this->authorize('delete', $storeConnection);

        $disconnectStore->execute($storeConnection);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Store disconnected and data purged.'),
        ]);

        return to_route('stores.index');
    }

    public function reconnect(
        ReconnectShopifyStoreRequest $request,
        StoreConnection $storeConnection,
        ReconnectShopifyStoreAction $reconnectShopifyStore,
    ): RedirectResponse {
        try {
            $reconnectShopifyStore->execute(
                connection: $storeConnection,
                accessToken: $request->validated('access_token'),
            );
        } catch (InvalidCredentialsException $exception) {
            return back()->withErrors([
                'access_token' => $exception->getMessage(),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Store reconnected successfully.'),
        ]);

        return to_route('stores.index');
    }
}
