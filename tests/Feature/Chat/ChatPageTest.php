<?php

declare(strict_types=1);

namespace Tests\Feature\Chat;

use App\Domains\Chat\Contracts\ChatService;
use App\Domains\Chat\DTOs\ActionStepDTO;
use App\Domains\Chat\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAgentFixtures;
use Tests\Concerns\SeedsPlans;
use Tests\TestCase;

class ChatPageTest extends TestCase
{
    use CreatesAgentFixtures;
    use RefreshDatabase;
    use SeedsPlans;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPlans();
    }

    public function test_renders_chat_page_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);

        $this->actingAs($user)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('chat/index')
                ->has('stores', 1)
                ->has('modelTiers')
                ->where('hasStores', true)
                ->where('hasValidByok', true)
                ->where('activeStoreId', $store->id));
    }

    public function test_loads_conversation_history_with_action_steps(): void
    {
        $user = User::factory()->create();
        $store = $this->createStoreForUser($user);
        $this->createOpenRouterCredential($user);
        $conversation = $this->createConversation($user, $store, ['title' => 'Orders today']);

        $chat = app(ChatService::class);
        $userMessage = $chat->appendUserMessage($conversation->id, 'Show recent orders');
        $assistantMessage = $chat->appendAssistantMessage(
            $conversation->id,
            'Here are your recent orders.',
            'openai/gpt-4o-mini',
        );
        $chat->recordActionStep($assistantMessage->id, new ActionStepDTO(
            id: '',
            stepOrder: 1,
            toolName: 'list_orders',
            arguments: ['limit' => 5],
            targetPlatform: 'shopify',
            status: 'done',
            isWrite: false,
            confirmed: null,
            resultSummary: ['total' => 2, 'rows' => []],
            durationMs: 120,
        ));

        $this->actingAs($user)
            ->get(route('chat.show', $conversation))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('chat/index')
                ->where('activeConversationId', $conversation->id)
                ->has('initialMessages', 2)
                ->where('initialMessages.0.content', 'Show recent orders')
                ->where('initialMessages.1.actionSteps.0.toolName', 'list_orders'));
    }

    public function test_guest_is_redirected_from_chat(): void
    {
        $this->get(route('chat.index'))->assertRedirect(route('login'));
    }

    public function test_user_cannot_view_another_accounts_conversation_page(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $store = $this->createStoreForUser($owner);
        $conversation = Conversation::query()->create([
            'account_id' => $owner->account_id,
            'user_id' => $owner->id,
            'store_connection_id' => $store->id,
            'title' => 'Private',
            'model' => 'openai/gpt-4o-mini',
        ]);

        $this->actingAs($intruder)
            ->get(route('chat.show', $conversation))
            ->assertNotFound();
    }
}
