<?php

declare(strict_types=1);

namespace Tests\Unit\Stores;

use App\Domains\Stores\Adapters\Shopify\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_normalizes_eur_amount_to_minor_units(): void
    {
        $this->assertSame(1230, Money::toMinor('12.30', 'EUR'));
    }

    public function test_respects_zero_decimal_currencies(): void
    {
        $this->assertSame(500, Money::toMinor('500', 'JPY'));
    }

    public function test_respects_three_decimal_currencies(): void
    {
        $this->assertSame(1234, Money::toMinor('1.234', 'KWD'));
    }

    public function test_null_amount_returns_zero(): void
    {
        $this->assertSame(0, Money::toMinor(null, 'EUR'));
    }
}
