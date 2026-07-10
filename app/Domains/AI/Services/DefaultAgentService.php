<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\AI\Contracts\AgentService;
use App\Domains\Shared\Concerns\DefersImplementation;

/**
 * Placeholder until Phase 8 (agent orchestration loop).
 */
final class DefaultAgentService implements AgentService
{
    use DefersImplementation;

    public function run(string $conversationId, string $userMessage): void
    {
        $this->notImplemented('AgentService');
    }

    public function resolveConfirmation(string $actionStepId, bool $confirmed): void
    {
        $this->notImplemented('AgentService');
    }
}
