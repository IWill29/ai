<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Domains\AI\Contracts\EmbeddingPort;
use App\Domains\AI\Contracts\MemoryService;
use App\Domains\AI\Services\VectorMemoryService;
use Tests\Support\DeterministicEmbeddingPort;

trait BindsDeterministicAgentMemory
{
    protected function bindDeterministicMemory(): void
    {
        $this->app->instance(EmbeddingPort::class, new DeterministicEmbeddingPort);
        $this->app->instance(MemoryService::class, new VectorMemoryService(app(EmbeddingPort::class)));
    }
}
