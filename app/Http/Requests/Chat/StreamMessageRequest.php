<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

final class StreamMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
            'model' => ['nullable', 'string', 'max:120'],
            'attachment_ids' => ['nullable', 'array', 'max:5'],
            'attachment_ids.*' => ['uuid'],
        ];
    }
}
