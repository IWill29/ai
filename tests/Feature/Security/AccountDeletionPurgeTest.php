<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domains\Accounts\Models\OpenRouterCredential;
use App\Domains\AI\Models\AgentMemory;
use App\Domains\Chat\Models\Conversation;
use App\Domains\Chat\Models\Message;
use App\Domains\Chat\Models\MessageAttachment;
use App\Domains\Stores\Enums\Platform;
use App\Domains\Stores\Models\StoreConnection;
use App\Domains\Stores\Models\StoreCredential;
use App\Domains\Stores\Models\SyncedOrder;
use App\Jobs\DeleteAccountJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Pgvector\Laravel\Vector;
use Tests\TestCase;

final class AccountDeletionPurgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_deletion_purges_credentials_synced_data_chat_memories_and_files(): void
    {
        Storage::fake('attachments');

        $user = User::factory()->create();
        $accountId = $user->account_id;

        $connection = StoreConnection::query()->create([
            'account_id' => $accountId,
            'platform' => Platform::Shopify->value,
            'name' => 'Demo',
            'domain' => 'demo.myshopify.com',
            'status' => 'active',
        ]);

        StoreCredential::query()->create([
            'store_connection_id' => $connection->id,
            'access_token' => 'shpat_test_token_1234567890',
            'secrets' => ['api_secret' => 'test_api_secret_key_12345'],
        ]);

        SyncedOrder::query()->create([
            'store_connection_id' => $connection->id,
            'external_id' => 'gid://shopify/Order/1',
            'order_number' => '#1001',
            'total_price_minor' => 1000,
        ]);

        OpenRouterCredential::query()->create([
            'account_id' => $accountId,
            'api_key' => 'sk-or-v1-test-key-abcdefghij',
        ]);

        $conversation = Conversation::query()->create([
            'account_id' => $accountId,
            'user_id' => $user->id,
            'store_connection_id' => $connection->id,
            'model' => 'openai/gpt-4.1-mini',
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Hello',
        ]);

        AgentMemory::query()->create([
            'account_id' => $accountId,
            'content' => 'Prefers eco packaging',
            'embedding' => new Vector(array_fill(0, 1536, 0.0)),
        ]);

        $storagePath = "{$accountId}/file.jpg";
        Storage::disk('attachments')->put($storagePath, 'binary');

        MessageAttachment::query()->create([
            'id' => (string) Str::uuid(),
            'account_id' => $accountId,
            'uploaded_by' => $user->id,
            'filename' => 'file.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 6,
            'storage_path' => $storagePath,
            'status' => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect(route('home'));

        $this->assertDatabaseMissing('openrouter_credentials', ['account_id' => $accountId]);
        $this->assertDatabaseMissing('store_credentials', ['store_connection_id' => $connection->id]);
        $this->assertDatabaseMissing('synced_orders', ['store_connection_id' => $connection->id]);
        $this->assertDatabaseMissing('conversations', ['account_id' => $accountId]);
        $this->assertDatabaseMissing('agent_memories', ['account_id' => $accountId]);
        $this->assertDatabaseMissing('message_attachments', ['account_id' => $accountId]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        Storage::disk('attachments')->assertMissing($storagePath);
    }
}
