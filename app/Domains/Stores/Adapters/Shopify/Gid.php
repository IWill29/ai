<?php

declare(strict_types=1);

namespace App\Domains\Stores\Adapters\Shopify;

final class Gid
{
    public static function idFromGid(string $gid): string
    {
        return $gid;
    }

    public static function ensureGid(string $externalId, string $resource): string
    {
        if (str_starts_with($externalId, 'gid://')) {
            return $externalId;
        }

        return "gid://shopify/{$resource}/{$externalId}";
    }
}
