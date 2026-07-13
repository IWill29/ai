<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Domains\AI\Services\ModelAllowList;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class ModelAllowListController extends Controller
{
    public function __invoke(ModelAllowList $models): JsonResponse
    {
        return response()->json(['tiers' => $models->forFrontend()]);
    }
}
