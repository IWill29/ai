<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Domains\Stores\Models\StoreConnection;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ChatController extends Controller
{
    public function index(Request $request): Response
    {
        $accountId = $request->user()->account_id;
        $requestedStoreId = $request->query('store_id');

        $connection = StoreConnection::query()
            ->where('account_id', $accountId)
            ->when(
                is_string($requestedStoreId) && $requestedStoreId !== '',
                fn ($query) => $query->whereKey($requestedStoreId),
            )
            ->orderBy('name')
            ->first();

        if ($connection === null && is_string($requestedStoreId) && $requestedStoreId !== '') {
            $connection = StoreConnection::query()
                ->where('account_id', $accountId)
                ->orderBy('name')
                ->first();
        }

        $prefillPrompt = $request->query('prompt');
        $prefillPrompt = is_string($prefillPrompt) && $prefillPrompt !== ''
            ? $prefillPrompt
            : null;

        return Inertia::render('chat/index', [
            'storeSync' => $this->storeSyncProps($connection),
            'prefillPrompt' => $prefillPrompt,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function storeSyncProps(?StoreConnection $connection): ?array
    {
        if ($connection === null) {
            return null;
        }

        $meta = is_array($connection->meta) ? $connection->meta : [];
        $sync = is_array($meta['sync'] ?? null) ? $meta['sync'] : [];

        return [
            'connectionId' => $connection->id,
            'storeName' => $connection->name,
            'state' => $sync['state'] ?? 'idle',
            'entity' => $sync['entity'] ?? null,
            'error' => $sync['error'] ?? null,
            'lastSyncedAt' => $connection->last_synced_at?->toIso8601String(),
            'status' => $connection->status,
        ];
    }
}
