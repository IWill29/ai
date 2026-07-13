<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\AI\Enums\ToolName;
use App\Domains\AI\Exceptions\ModelNotAllowedException;
use App\Domains\AI\Services\ModelAllowList;
use App\Domains\AI\Tools\ToolRegistry;
use Tests\TestCase;

class ModelAllowListTest extends TestCase
{
    public function test_rejects_disallowed_models(): void
    {
        $this->expectException(ModelNotAllowedException::class);

        app(ModelAllowList::class)->assertAllowed('gpt-3.5-turbo');
    }

    public function test_allows_configured_models(): void
    {
        app(ModelAllowList::class)->assertAllowed('openai/gpt-4o-mini');

        $this->assertTrue(true);
    }

    public function test_for_frontend_returns_three_tiers(): void
    {
        $tiers = app(ModelAllowList::class)->forFrontend();

        $this->assertCount(3, $tiers);
        $this->assertSame('Budget', $tiers[0]['tier']);
    }
}

class ToolRegistryExhaustiveTest extends TestCase
{
    public function test_registry_covers_every_tool_name(): void
    {
        $registry = app(ToolRegistry::class);
        $definitions = $registry->all();

        $this->assertCount(count(ToolName::cases()), $definitions);

        foreach (ToolName::cases() as $tool) {
            $definition = $registry->for($tool);
            $this->assertSame($tool->value, $definition->name);
            $this->assertSame($tool->isWrite(), $definition->isWrite);
        }
    }
}
