<?php

declare(strict_types=1);

namespace App\Domains\AI\Support;

final class OpenRouterModels
{
    public const GPT_4O_MINI = 'openai/gpt-4o-mini';

    public const CLAUDE_HAIKU = 'anthropic/claude-3.5-haiku';

    public const DEEPSEEK_CHAT = 'deepseek/deepseek-chat';

    public const GPT_4O = 'openai/gpt-4o';

    public const CLAUDE_SONNET = 'anthropic/claude-3.5-sonnet';

    public const CLAUDE_OPUS = 'anthropic/claude-3-opus';

    public const O1 = 'openai/o1';

    /** @return array<int, string> */
    public static function suggested(): array
    {
        return [
            self::GPT_4O_MINI,
            self::CLAUDE_HAIKU,
            self::DEEPSEEK_CHAT,
            self::GPT_4O,
            self::CLAUDE_SONNET,
            self::CLAUDE_OPUS,
        ];
    }

    /** @return array<string, array<int, string>> */
    public static function tiers(): array
    {
        return [
            'budget' => [self::GPT_4O_MINI, self::CLAUDE_HAIKU, self::DEEPSEEK_CHAT],
            'balanced' => [self::GPT_4O, self::CLAUDE_SONNET],
            'premium' => [self::CLAUDE_OPUS, self::O1],
        ];
    }

    /** @return array<string, array<int, string>> */
    public static function fallbacks(): array
    {
        return [
            self::GPT_4O_MINI => [self::CLAUDE_HAIKU],
            self::GPT_4O => [self::CLAUDE_SONNET],
            self::CLAUDE_OPUS => [self::GPT_4O],
        ];
    }
}
