<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Domains\Accounts\Models\OpenRouterCredential;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Models\StoreConnection;
use App\Models\User;
use Illuminate\Support\Carbon;

trait CreatesAgentFixtures
{
    protected function createStoreForUser(User $user, array $attributes = []): StoreConnection
    {
        return StoreConnection::query()->create(array_merge([
            'account_id' => $user->account_id,
            'platform' => Platform::Shopify->value,
            'name' => 'Demo Store',
            'domain' => 'demo.myshopify.com',
            'status' => 'active',
            'meta' => ['shop' => ['currency' => 'EUR']],
        ], $attributes));
    }

    protected function createConversation(User $user, StoreConnection $store, array $attributes = []): Conversation
    {
        return Conversation::query()->create(array_merge([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'store_connection_id' => $store->id,
            'model' => 'openai/gpt-4o-mini',
        ], $attributes));
    }

    protected function createOpenRouterCredential(User $user, array $attributes = []): OpenRouterCredential
    {
        return OpenRouterCredential::query()->create(array_merge([
            'account_id' => $user->account_id,
            'api_key' => 'sk-or-v1-test-key-abcdefghij',
            'default_model' => 'openai/gpt-4o-mini',
            'validated_at' => Carbon::now(),
        ], $attributes));
    }
}
