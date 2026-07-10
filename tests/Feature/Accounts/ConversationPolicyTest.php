<?php

declare(strict_types=1);

namespace Tests\Feature\Accounts;

use App\Domains\Chat\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_another_accounts_conversation(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $conversation = Conversation::query()->create([
            'account_id' => $owner->account_id,
            'user_id' => $owner->id,
            'title' => 'Ops',
            'model' => 'openai/gpt-4o-mini',
        ]);

        $this->assertFalse($intruder->can('view', $conversation));
        $this->assertTrue($owner->can('view', $conversation));
    }
}
