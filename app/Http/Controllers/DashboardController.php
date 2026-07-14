<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domains\Dashboard\Services\DashboardTableReader;
use App\Domains\Dashboard\Services\SyncedMetricsReader;
use App\Domains\Stores\Models\StoreConnection;
use App\Http\Requests\DashboardFilterRequest;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function index(DashboardFilterRequest $request): Response
    {
        $this->authorize('viewAny', StoreConnection::class);

        $accountId = $request->user()->account_id;

        $stores = StoreConnection::query()
            ->where('account_id', $accountId)
            ->orderBy('name')
            ->get(['id', 'name', 'domain', 'status', 'last_synced_at', 'meta', 'account_id']);

        if ($stores->isEmpty()) {
            return Inertia::render('dashboard', [
                'hasStores' => false,
                'stores' => [],
            ]);
        }

        $storeId = $request->validated('store_id') ?? $stores->first()->id;
        $store = $stores->firstWhere('id', $storeId);

        if ($store === null) {
            abort(403);
        }

        $this->authorize('view', $store);

        [$from, $to] = $request->dateRange();

        $lastSyncedAt = $store->last_synced_at;

        return Inertia::render('dashboard', [
            'hasStores' => true,
            'stores' => $stores->map(function (StoreConnection $connection): array {
                $syncedAt = $connection->last_synced_at;

                return [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'domain' => $connection->domain,
                    'status' => $connection->status,
                    'lastSyncedAt' => $syncedAt instanceof Carbon
                        ? $syncedAt->toIso8601String()
                        : null,
                ];
            })->values()->all(),
            'filters' => [
                'store_id' => $storeId,
                'range' => $request->validated('range', '30d'),
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'lastSyncedAt' => $lastSyncedAt instanceof Carbon
                ? $lastSyncedAt->toIso8601String()
                : null,
            'selectedStoreStatus' => $store->status,
            'metrics' => Inertia::defer(
                fn () => app(SyncedMetricsReader::class)->forStore($store, $from, $to),
                'dashboard',
            ),
            'topProducts' => Inertia::defer(
                fn () => app(DashboardTableReader::class)->topProducts($store, $from, $to),
                'dashboard',
            ),
            'recentOrders' => Inertia::defer(
                fn () => app(DashboardTableReader::class)->recentOrders($store),
                'dashboard',
            ),
        ]);
    }
}
