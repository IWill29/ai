<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Domains\Stores\Models\StoreConnection;
use App\Http\Requests\Concerns\InteractsWithOwnedResources;
use App\Rules\AllowedAgentModel;
use Illuminate\Foundation\Http\FormRequest;

final class StoreConversationRequest extends FormRequest
{
    use InteractsWithOwnedResources;

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', StoreConnection::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'store_connection_id' => ['required', 'uuid', $this->ownedStoreConnectionRule()],
            'model' => ['nullable', 'string', 'max:120', new AllowedAgentModel],
        ];
    }
}
