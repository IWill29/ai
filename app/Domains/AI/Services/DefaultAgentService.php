<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\AI\Contracts\AgentService;
use BadMethodCallException;

/**
 * Placeholder until Phase 8 (agent orchestration loop).
 */
final class DefaultAgentService implements AgentService
{
    public function run(string $conversationId, string $userMessage): void
    {
        throw new BadMethodCallException('AgentService not implemented until Phase 8.');
    }

    public function resolveConfirmation(string $actionStepId, bool $confirmed): void
    {
        throw new BadMethodCallException('AgentService not implemented until Phase 8.');
    }
}
