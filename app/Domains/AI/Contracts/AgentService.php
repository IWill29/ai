<?php

declare(strict_types=1);

namespace App\Domains\AI\Contracts;

/**
 * Agent orchestration — one turn: plan → tool calls → answer (persisted via ChatService).
 * Results stream through ChatService/SSE; not returned inline (ADR 010).
 */
interface AgentService
{
    /**
     * Run one agent turn with SSE events.
     *
     * @param  callable(string, array<string, mixed>): void  $emit
     * @param  array<int, string>  $attachmentIds
     */
    public function runWithStream(
        string $conversationId,
        string $userMessage,
        callable $emit,
        array $attachmentIds = [],
    ): void;

    /**
     * Continue a paused agent turn after write confirmation.
     *
     * @param  callable(string, array<string, mixed>): void  $emit
     */
    public function resumeWithStream(string $conversationId, callable $emit): void;

    /** User confirmed or declined a pending write step. */
    public function resolveConfirmation(string $actionStepId, bool $confirmed): void;
}
