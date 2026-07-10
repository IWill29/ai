<?php

declare(strict_types=1);

namespace App\Domains\AI\Contracts;

/**
 * Agent orchestration — one turn: plan → tool calls → answer (persisted via ChatService).
 * Results stream through ChatService/SSE; not returned inline (ADR 010).
 */
interface AgentService
{
    /** Run one agent turn for a conversation. */
    public function run(string $conversationId, string $userMessage): void;

    /** User confirmed or declined a pending write step. */
    public function resolveConfirmation(string $actionStepId, bool $confirmed): void;
}
