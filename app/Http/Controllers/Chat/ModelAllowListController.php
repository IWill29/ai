<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Domains\Accounts\Models\OpenRouterCredential;
use App\Domains\AI\Services\ModelAllowList;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ModelAllowListController extends Controller
{
    public function __invoke(Request $request, ModelAllowList $models): JsonResponse
    {
        $this->authorize('viewAny', OpenRouterCredential::class);

        return response()->json(['tiers' => $models->forFrontend()]);
    }
}
