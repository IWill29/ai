<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Accounts\Contracts\AccountService;
use App\Domains\Accounts\Services\DefaultAccountService;
use App\Domains\AI\Contracts\AgentLlmPort;
use App\Domains\AI\Contracts\AgentService;
use App\Domains\AI\Contracts\MemoryService;
use App\Domains\AI\Services\DefaultAgentService;
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
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * Domain contract bindings — swap implementations in later phases.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        StoreAdapterFactory::class => StoreAdapterManager::class,
        AgentLlmPort::class => OpenRouterAdapter::class,
        AgentService::class => DefaultAgentService::class,
        MemoryService::class => StubMemoryService::class,
        ChatService::class => DefaultChatService::class,
        AttachmentUploadService::class => StubAttachmentUploadService::class,
        BillingService::class => StubBillingService::class,
        AccountService::class => DefaultAccountService::class,
        MetricsReader::class => SyncedMetricsReader::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
