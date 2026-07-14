<?php

declare(strict_types=1);

namespace Tests\Feature\AI;

use App\Domains\AI\Contracts\EmbeddingPort;
use App\Domains\AI\Contracts\MemoryService;
use App\Domains\AI\Models\AgentMemory;
use App\Domains\AI\Services\VectorMemoryService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BindsDeterministicAgentMemory;
use Tests\Concerns\CreatesAgentFixtures;
use Tests\Support\AgentTestData;
use Tests\Support\FailingEmbeddingPort;
use Tests\TestCase;

class AgentMemoryRecallTest extends TestCase
{
    use BindsDeterministicAgentMemory;
    use CreatesAgentFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bindDeterministicMemory();
    }

    public function test_recall_returns_relevant_memories_for_same_account(): void
    {
        $user = User::factory()->create();
        $this->createOpenRouterCredential($user);

        $memory = app(MemoryService::class);
        $memory->remember(
            $user->account_id,
            AgentTestData::MEMORY_PREFERENCE_KEEP_BRIEF,
            ['source' => 'merchant_preference'],
        );

        $recalled = $memory->recall($user->account_id, 'Give me a brief summary of sales');

        $this->assertCount(1, $recalled);
        $this->assertSame(AgentTestData::MEMORY_PREFERENCE_KEEP_BRIEF, $recalled[0]['content']);
        $this->assertSame('merchant_preference', $recalled[0]['meta']['source']);
    }

    public function test_recall_is_isolated_per_account(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $this->createOpenRouterCredential($userA);
        $this->createOpenRouterCredential($userB);

        $memory = app(MemoryService::class);
        $memory->remember($userA->account_id, AgentTestData::MEMORY_PREFERENCE_KEEP_BRIEF);

        $recalled = $memory->recall($userB->account_id, 'brief summary please');

        $this->assertSame([], $recalled);
        $this->assertSame(1, AgentMemory::query()->where('account_id', $userA->account_id)->count());
        $this->assertSame(0, AgentMemory::query()->where('account_id', $userB->account_id)->count());
    }

    public function test_recall_works_across_separate_conversations(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);

        $memory = app(MemoryService::class);
        $memory->remember(
            $user->account_id,
            AgentTestData::MEMORY_CONFIRMED_FULFILL_ORDER_100,
            ['source' => 'confirmed_action'],
        );

        $firstConversation = $this->createConversation($user, $store, ['title' => 'Ops thread A']);
        $secondConversation = $this->createConversation($user, $store, ['title' => 'Ops thread B']);

        $this->assertNotSame($firstConversation->id, $secondConversation->id);

        $recalled = $memory->recall($user->account_id, 'Can you fulfill order 100 again?');

        $this->assertCount(1, $recalled);
        $this->assertStringContainsString(AgentTestData::MEMORY_FULFILL_PHRASE, $recalled[0]['content']);
        $this->assertSame('confirmed_action', $recalled[0]['meta']['source']);
    }

    public function test_recall_returns_empty_for_empty_query(): void
    {
        $user = User::factory()->create();
        $this->createOpenRouterCredential($user);

        app(MemoryService::class)->remember($user->account_id, AgentTestData::MEMORY_PREFERENCE_KEEP_BRIEF);

        $recalled = app(MemoryService::class)->recall($user->account_id, '');

        $this->assertSame([], $recalled);
    }

    public function test_recall_respects_limit(): void
    {
        $user = User::factory()->create();
        $this->createOpenRouterCredential($user);
        $memory = app(MemoryService::class);

        foreach (['brief style one', 'brief style two', 'brief style three', 'brief style four'] as $content) {
            $memory->remember($user->account_id, "Merchant preference: {$content}");
        }

        $this->assertSame(4, AgentMemory::query()->where('account_id', $user->account_id)->count());

        $recalled = $memory->recall($user->account_id, 'brief summary please', 2);

        $this->assertCount(2, $recalled);
    }

    public function test_embedding_failure_skips_persisting_memory(): void
    {
        $this->app->instance(EmbeddingPort::class, new FailingEmbeddingPort);
        $this->app->instance(MemoryService::class, new VectorMemoryService(app(EmbeddingPort::class)));

        $user = User::factory()->create();
        $this->createOpenRouterCredential($user);

        app(MemoryService::class)->remember($user->account_id, AgentTestData::MEMORY_PREFERENCE_BRIEF_ANSWERS);

        $this->assertSame(0, AgentMemory::query()->where('account_id', $user->account_id)->count());
    }

    public function test_recall_prefers_semantically_matching_memory(): void
    {
        $user = User::factory()->create();
        $this->createOpenRouterCredential($user);
        $memory = app(MemoryService::class);

        $memory->remember($user->account_id, AgentTestData::MEMORY_PREFERENCE_KEEP_BRIEF);
        $memory->remember($user->account_id, AgentTestData::MEMORY_CONFIRMED_FULFILL_ORDER_100);

        $recalled = $memory->recall($user->account_id, 'Can you fulfill order 100?', 1);

        $this->assertCount(1, $recalled);
        $this->assertStringContainsString(AgentTestData::MEMORY_FULFILL_PHRASE, $recalled[0]['content']);
    }
}
