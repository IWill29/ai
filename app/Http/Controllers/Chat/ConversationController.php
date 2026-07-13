<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Domains\Chat\Contracts\ChatService;
use App\Domains\Stores\Models\StoreConnection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreConversationRequest;
use Illuminate\Http\JsonResponse;

final class ConversationController extends Controller
{
    public function store(StoreConversationRequest $request, ChatService $chat): JsonResponse
    {
        $store = StoreConnection::query()->findOrFail($request->string('store_connection_id')->value());
        $this->authorize('view', $store);

        $conversation = $chat->startConversation(
            accountId: (string) $request->user()->account_id,
            userId: (string) $request->user()->id,
            storeConnectionId: $store->id,
            model: $request->string('model')->toString() ?: null,
        );

        return response()->json(['conversation' => $conversation]);
    }
}
