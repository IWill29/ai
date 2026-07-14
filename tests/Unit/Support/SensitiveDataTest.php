<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\SensitiveData;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SensitiveDataTest extends TestCase
{
    #[Test]
    public function it_redacts_credential_keys_from_context(): void
    {
        $redacted = SensitiveData::redactContext([
            'external_id' => '123',
            'access_token' => 'shpat_test_token_1234567890',
            'nested' => [
                'api_key' => 'sk-or-v1-test-key',
            ],
        ]);

        $this->assertSame('123', $redacted['external_id']);
        $this->assertSame(SensitiveData::REDACTED, $redacted['access_token']);
        $this->assertSame(SensitiveData::REDACTED, $redacted['nested']['api_key']);
    }

    #[Test]
    public function it_redacts_customer_pii_from_context(): void
    {
        $redacted = SensitiveData::redactContext([
            'external_id' => 'cust-1',
            'email' => 'buyer@example.com',
            'shipping_address' => ['line1' => '123 Main St'],
            'note' => 'Call before delivery',
        ]);

        $this->assertSame('cust-1', $redacted['external_id']);
        $this->assertSame(SensitiveData::REDACTED, $redacted['email']);
        $this->assertSame(SensitiveData::REDACTED, $redacted['shipping_address']);
        $this->assertSame(SensitiveData::REDACTED, $redacted['note']);
    }

    #[Test]
    public function it_strips_http_response_bodies_from_messages(): void
    {
        $message = SensitiveData::sanitizeMessage(
            'OpenRouter HTTP 502: {"error":{"message":"prompt leaked","api_key":"sk-or-v1-secret"}}',
        );

        $this->assertStringNotContainsString('prompt leaked', $message);
        $this->assertStringNotContainsString('sk-or-v1-secret', $message);
        $this->assertStringContainsString('HTTP 502', $message);
    }

    #[Test]
    public function it_does_not_redact_innocent_keys_with_embedded_sensitive_substrings(): void
    {
        $redacted = SensitiveData::redactContext([
            'external_id' => '123',
            'monotone' => 'steady',
            'product_id' => 'prod-1',
        ]);

        $this->assertSame('123', $redacted['external_id']);
        $this->assertSame('steady', $redacted['monotone']);
        $this->assertSame('prod-1', $redacted['product_id']);
    }

    #[Test]
    public function it_redacts_token_patterns_from_messages(): void
    {
        $message = SensitiveData::sanitizeMessage(
            'Authorization failed for shpat_test_token_1234567890 and Bearer sk-or-v1-test-key',
        );

        $this->assertStringNotContainsString('shpat_test_token_1234567890', $message);
        $this->assertStringNotContainsString('sk-or-v1-test-key', $message);
        $this->assertStringContainsString(SensitiveData::REDACTED, $message);
    }
}
