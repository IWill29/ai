<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $maxKb = (int) (config('agent.attachment.max_size_bytes', 5 * 1024 * 1024) / 1024);

        return [
            'file' => ['required', 'file', 'max:'.$maxKb, 'mimes:jpeg,jpg,png,webp,gif'],
        ];
    }
}
