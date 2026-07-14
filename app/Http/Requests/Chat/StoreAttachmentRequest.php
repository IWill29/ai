<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Domains\Chat\Models\MessageAttachment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

final class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MessageAttachment::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $maxKb = (int) (config('agent.attachment.max_size_bytes', 5 * 1024 * 1024) / 1024);

        return [
            'file' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'webp', 'gif'])->max($maxKb),
            ],
        ];
    }
}
