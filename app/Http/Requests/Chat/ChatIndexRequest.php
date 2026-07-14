<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Domains\Stores\Models\StoreConnection;
use App\Http\Requests\Concerns\InteractsWithOwnedResources;
use Illuminate\Foundation\Http\FormRequest;

final class ChatIndexRequest extends FormRequest
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
            'store_id' => ['nullable', 'uuid', $this->ownedStoreConnectionRule()],
            'prompt' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
