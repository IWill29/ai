<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Accounts\Contracts\AccountService;
use App\Domains\Accounts\Services\DefaultAccountService;
use App\Domains\AI\Contracts\AgentLlmPort;
use App\Domains\AI\Contracts\AgentService;
use App\Domains\AI\Contracts\MemoryService;
use App\Domains\AI\Services\OpenRouterAdapter;
use App\Domains\AI\Services\StubMemoryService;
use App\Domains\Billing\Contracts\BillingService;
use App\Domains\Billing\Services\StubBillingService;
use App\Domains\Chat\Contracts\AttachmentUploadService;
use App\Domains\Chat\Contracts\ChatService;
use App\Domains\Chat\Services\DefaultChatService;
use App\Domains\Chat\Services\StubAttachmentUploadService;
use App\Domains\Dashboard\Contracts\MetricsReader;
use App\Domains\Dashboard\Services\SyncedMetricsReader;
use App\Domains\Stores\Contracts\StoreAdapterFactory;
use App\Domains\Stores\Services\StoreAdapterManager;
use Tests\TestCase;

class DomainContractBindingsTest extends TestCase
{
    public function test_store_adapter_factory_resolves(): void
    {
        $this->assertInstanceOf(StoreAdapterManager::class, app(StoreAdapterFactory::class));
    }

    public function test_agent_llm_port_resolves(): void
    {
        $this->assertInstanceOf(OpenRouterAdapter::class, app(AgentLlmPort::class));
    }

    public function test_agent_service_resolves(): void
    {
        $this->assertInstanceOf(AgentService::class, app(AgentService::class));
    }

    public function test_memory_service_resolves_to_stub(): void
    {
        $this->assertInstanceOf(StubMemoryService::class, app(MemoryService::class));
    }

    public function test_chat_service_resolves(): void
    {
        $this->assertInstanceOf(DefaultChatService::class, app(ChatService::class));
    }

    public function test_attachment_upload_service_resolves(): void
    {
        $this->assertInstanceOf(StubAttachmentUploadService::class, app(AttachmentUploadService::class));
    }

    public function test_billing_service_resolves_to_stub(): void
    {
        $this->assertInstanceOf(StubBillingService::class, app(BillingService::class));
    }

    public function test_account_service_resolves(): void
    {
        $this->assertInstanceOf(DefaultAccountService::class, app(AccountService::class));
    }

    public function test_metrics_reader_resolves(): void
    {
        $this->assertInstanceOf(SyncedMetricsReader::class, app(MetricsReader::class));
    }
}
