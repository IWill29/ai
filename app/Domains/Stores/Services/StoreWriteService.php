<?php

declare(strict_types=1);

namespace App\Domains\Stores\Services;

use App\Domains\Stores\Actions\RecordStoreWriteAuditAction;
use App\Domains\Stores\Contracts\StoreAdapterFactory;
use App\Domains\Stores\Contracts\StorePort;
use App\Domains\Stores\Models\StoreConnection;
use App\Models\User;

/**
 * Executes store writes with audit logging — adapter stays pure.
 */
final class StoreWriteService
{
    public function __construct(
        private readonly StoreAdapterFactory $adapterFactory,
        private readonly RecordStoreWriteAuditAction $recordAudit,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function execute(
        StoreConnection $connection,
        User $user,
        string $action,
        callable $write,
        array $context = [],
    ): mixed {
        $adapter = $this->adapterFactory->for($connection);

        $result = $write($adapter);

        $this->recordAudit->execute(
            accountId: $connection->account_id,
            userId: $user->id,
            storeConnectionId: $connection->id,
            action: $action,
            context: $context,
        );

        return $result;
    }

    public function adapterFor(StoreConnection $connection): StorePort
    {
        return $this->adapterFactory->for($connection);
    }
}
