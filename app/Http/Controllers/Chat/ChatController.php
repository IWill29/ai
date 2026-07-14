<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Domains\Accounts\Models\OpenRouterCredential;
use App\Domains\AI\Services\ModelAllowList;
use App\Domains\Chat\Contracts\ChatService;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Stores\Models\StoreConnection;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ChatController extends Controller
{
    public function index(Request $request, ChatService $chat, ModelAllowList $models): Response
    {
        return $this->renderChatPage($request, $chat, $models);
    }

    public function show(Request $request, Conversation $conversation, ChatService $chat, ModelAllowList $models): Response
    {
        $this->authorize('view', $conversation);

        return $this->renderChatPage(
            $request,
            $chat,
            $models,
            $conversation,
        );
    }

    private function renderChatPage(
        Request $request,
        ChatService $chat,
        ModelAllowList $models,
        ?Conversation $activeConversation = null,
    ): Response {
        $accountId = (string) $request->user()->account_id;
        $requestedStoreId = $request->query('store_id');

        $stores = StoreConnection::query()
            ->where('account_id', $accountId)
            ->where('status', '!=', 'disconnected')
            ->orderBy('name')
            ->get(['id', 'name', 'domain', 'status', 'last_synced_at', 'meta']);

        $activeStoreId = $activeConversation?->store_connection_id
            ?? (is_string($requestedStoreId) && $requestedStoreId !== '' ? $requestedStoreId : null)
            ?? $stores->first()?->id;

        $activeStore = $stores->firstWhere('id', $activeStoreId);

        $prefillPrompt = $request->query('prompt');
        $prefillPrompt = is_string($prefillPrompt) && $prefillPrompt !== ''
            ? $prefillPrompt
            : null;

        $credential = OpenRouterCredential::query()
            ->where('account_id', $accountId)
            ->first();

        return Inertia::render('chat/index', [
            'stores' => $stores->map(fn (StoreConnection $store) => [
                'id' => $store->id,
                'name' => $store->name,
                'domain' => $store->domain,
                'status' => $store->status,
                'lastSyncedAt' => $store->last_synced_at?->toIso8601String(),
            ])->values(),
            'hasStores' => $stores->isNotEmpty(),
            'hasValidByok' => $credential?->validated_at !== null,
            'modelTiers' => $models->forFrontend(),
            'conversations' => $chat->listConversations($accountId),
            'activeStoreId' => $activeStoreId,
            'storeSync' => $this->storeSyncProps($activeStore),
            'prefillPrompt' => $prefillPrompt,
            'activeConversationId' => $activeConversation?->id,
            'initialMessages' => $activeConversation !== null
                ? $chat->getHistory($activeConversation->id)
                : [],
            'defaultModel' => $credential?->default_model ?? config('openrouter.default_model'),
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
