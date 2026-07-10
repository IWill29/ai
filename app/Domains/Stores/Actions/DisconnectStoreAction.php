<?php

declare(strict_types=1);

namespace App\Domains\Stores\Actions;

use App\Domains\Stores\Models\StoreConnection;
use Illuminate\Support\Facades\DB;

final class DisconnectStoreAction
{
    public function execute(StoreConnection $connection): void
    {
        DB::transaction(function () use ($connection): void {
            $connection->forceDelete();
        });
    }
}
