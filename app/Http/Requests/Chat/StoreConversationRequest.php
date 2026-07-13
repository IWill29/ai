<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

final class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'store_connection_id' => ['required', 'uuid', 'exists:store_connections,id'],
            'model' => ['nullable', 'string', 'max:120'],
        ];
    }
}
