<?php

declare(strict_types=1);

namespace App\Http\Controllers\Chat;

use App\Domains\AI\Contracts\AgentService;
use App\Domains\Chat\Models\ActionStep;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ConfirmActionRequest;
use Illuminate\Http\JsonResponse;

final class ConfirmationController extends Controller
{
    public function store(
        ConfirmActionRequest $request,
        ActionStep $actionStep,
        AgentService $agent,
    ): JsonResponse {
        $agent->resolveConfirmation($actionStep->id, $request->boolean('confirmed'));

        return response()->json(['status' => 'resolved']);
    }
}
