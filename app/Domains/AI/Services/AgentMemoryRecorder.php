<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\AI\Contracts\MemoryService;
use App\Domains\AI\Enums\MemorySource;
use App\Domains\AI\Enums\ToolName;

final class AgentMemoryRecorder
{
    public function __construct(
        private readonly MemoryService $memory,
        private readonly WriteActionDescriber $describer,
        private readonly MerchantPreferenceDetector $preferences,
    ) {}

    public function recordPreferenceIfPresent(string $accountId, string $message): void
    {
        $preference = $this->preferences->extract($message);

        if ($preference === null) {
            return;
        }

        $this->memory->remember($accountId, "Merchant preference: {$preference}", [
            'source' => MemorySource::MerchantPreference->value,
        ]);
    }

    /** @param array<string, mixed> $args */
    public function recordConfirmedAction(
        string $accountId,
        ToolName $tool,
        array $args,
        string $actionStepId,
    ): void {
        $this->memory->remember(
            $accountId,
            'Merchant confirmed: '.$this->describer->describe($tool, $args),
            [
                'source' => MemorySource::ConfirmedAction->value,
                'tool' => $tool->value,
                'action_step_id' => $actionStepId,
            ],
        );
    }
}
