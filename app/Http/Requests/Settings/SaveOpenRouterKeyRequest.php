<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Domains\Accounts\Models\OpenRouterCredential;
use App\Rules\AllowedAgentModel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveOpenRouterKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || $user->account_id === null) {
            return false;
        }

        $credential = OpenRouterCredential::query()
            ->where('account_id', $user->account_id)
            ->first();

        if ($credential !== null) {
            return $user->can('update', $credential);
        }

        return $user->can('create', OpenRouterCredential::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'api_key' => ['required', 'string', 'min:20', 'max:255'],
            'default_model' => ['nullable', 'string', 'max:120', new AllowedAgentModel],
        ];
    }
}
