<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\AI\Enums\ToolName;
use Tests\TestCase;

class ToolNameExhaustiveTest extends TestCase
{
    public function test_is_write_classifies_every_tool(): void
    {
        foreach (ToolName::cases() as $tool) {
            $this->assertIsBool($tool->isWrite());
        }
    }

    public function test_read_tools_are_not_write(): void
    {
        $this->assertFalse(ToolName::ListOrders->isWrite());
        $this->assertFalse(ToolName::GetMetrics->isWrite());
    }

    public function test_write_tools_are_write(): void
    {
        $this->assertTrue(ToolName::UpdateOrder->isWrite());
        $this->assertTrue(ToolName::CreateProduct->isWrite());
    }
}
