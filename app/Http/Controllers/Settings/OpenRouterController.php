<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Domains\Accounts\Actions\SaveOpenRouterKeyAction;
use App\Domains\Accounts\Exceptions\InvalidApiKeyException;
use App\Domains\Accounts\Models\OpenRouterCredential;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SaveOpenRouterKeyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OpenRouterController extends Controller
{
    public function edit(Request $request): Response
    {
        $this->authorize('viewAny', OpenRouterCredential::class);

        $credential = OpenRouterCredential::query()
            ->where('account_id', $request->user()->account_id)
            ->first();

        return Inertia::render('settings/open-router', [
            'openRouter' => [
                'configured' => $credential !== null,
                'maskedKey' => $credential ? $this->maskApiKey($credential->api_key) : null,
                'validatedAt' => $credential?->validated_at?->toIso8601String(),
                'defaultModel' => $credential?->default_model,
            ],
            'suggestedModels' => config('openrouter.suggested_models', []),
        ]);
    }

    public function store(
        SaveOpenRouterKeyRequest $request,
        SaveOpenRouterKeyAction $saveOpenRouterKey,
    ): RedirectResponse {
        $credential = OpenRouterCredential::query()
            ->where('account_id', $request->user()->account_id)
            ->first();

        if ($credential !== null) {
            $this->authorize('update', $credential);
        } else {
            $this->authorize('create', OpenRouterCredential::class);
        }

        try {
            $saveOpenRouterKey->execute(
                accountId: $request->user()->account_id,
                userId: $request->user()->id,
                apiKey: $request->validated('api_key'),
                defaultModel: $request->validated('default_model'),
            );
        } catch (InvalidApiKeyException $exception) {
            throw ValidationException::withMessages([
                'api_key' => $exception->getMessage(),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('OpenRouter key saved and validated.')]);

        return to_route('settings.openrouter');
    }

    private function maskApiKey(string $apiKey): string
    {
        if (strlen($apiKey) <= 8) {
            return '••••••••';
        }

        return substr($apiKey, 0, 6).'…'.substr($apiKey, -4);
    }
}
