<?php

declare(strict_types=1);

namespace Tests\Unit\AI;

use App\Domains\AI\Services\MerchantPreferenceDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MerchantPreferenceDetectorTest extends TestCase
{
    private MerchantPreferenceDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detector = new MerchantPreferenceDetector;
    }

    #[DataProvider('preferenceMessagesProvider')]
    public function test_extracts_explicit_merchant_preferences(string $message, string $expected): void
    {
        $this->assertSame($expected, $this->detector->extract($message));
    }

    public static function preferenceMessagesProvider(): array
    {
        return [
            'remember that' => ['Remember that I want brief answers', 'I want brief answers'],
            'from now on' => ['From now on, use EUR formatting', 'use EUR formatting'],
            'always' => ['Always summarize orders in one line', 'summarize orders in one line'],
            'my preference' => ['My preference is short bullet lists', 'short bullet lists'],
            'i prefer' => ['I prefer to see SKU in product lists', 'see SKU in product lists'],
        ];
    }

    public function test_ignores_regular_operational_messages(): void
    {
        $this->assertNull($this->detector->extract('Show me unfulfilled orders from yesterday'));
    }
}
