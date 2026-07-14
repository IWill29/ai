<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Domains\Chat\Models\MessageAttachment;
use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Models\StoreConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesAgentFixtures;
use Tests\Concerns\SeedsPlans;
use Tests\TestCase;

final class FormRequestValidationTest extends TestCase
{
    use CreatesAgentFixtures;
    use RefreshDatabase;
    use SeedsPlans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
    }

    public function test_conversation_store_rejects_foreign_store_connection(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $store = $this->createStoreForAccount($owner->account_id);

        $this->actingAs($intruder)
            ->postJson(route('conversations.store'), [
                'store_connection_id' => $store->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('store_connection_id');
    }

    public function test_chat_index_rejects_foreign_store_id(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $store = $this->createStoreForAccount($owner->account_id);

        $this->actingAs($intruder)
            ->get(route('chat.index', ['store_id' => $store->id]))
            ->assertSessionHasErrors('store_id');
    }

    public function test_chat_index_rejects_oversized_prompt(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('chat.index', ['prompt' => str_repeat('a', 4001)]))
            ->assertSessionHasErrors('prompt');
    }

    public function test_stream_rejects_disallowed_model(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store);

        $this->actingAs($user)
            ->postJson(route('conversations.stream', $conversation), [
                'message' => 'Hi',
                'model' => 'gpt-3.5-turbo',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('model');
    }

    public function test_stream_rejects_foreign_attachment_ids(): void
    {
        Storage::fake('attachments');

        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $store = $this->createStoreForUser($intruder);
        $this->createOpenRouterCredential($intruder);
        $conversation = $this->createConversation($intruder, $store);

        $attachment = MessageAttachment::query()->create([
            'id' => (string) Str::uuid(),
            'account_id' => $owner->account_id,
            'uploaded_by' => $owner->id,
            'filename' => 'product.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'storage_path' => 'attachments/product.jpg',
            'status' => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        $this->actingAs($intruder)
            ->postJson(route('conversations.stream', $conversation), [
                'message' => 'Update product image',
                'attachment_ids' => [$attachment->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachment_ids.0');
    }

    public function test_openrouter_settings_reject_disallowed_default_model(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('settings.openrouter'))
            ->post(route('settings.openrouter.store'), [
                'api_key' => 'sk-or-v1-test-key-abcdefghij',
                'default_model' => 'gpt-3.5-turbo',
            ])
            ->assertSessionHasErrors('default_model');
    }

    private function createStoreForAccount(string $accountId): StoreConnection
    {
        return StoreConnection::query()->create([
            'account_id' => $accountId,
            'platform' => Platform::Shopify->value,
            'name' => 'Demo Store',
            'domain' => 'demo.myshopify.com',
            'status' => 'active',
        ]);
    }
}
