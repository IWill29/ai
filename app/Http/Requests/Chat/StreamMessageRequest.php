<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Domains\Chat\Models\Conversation;
use App\Http\Requests\Concerns\InteractsWithOwnedResources;
use App\Rules\AllowedAgentModel;
use Illuminate\Foundation\Http\FormRequest;

final class StreamMessageRequest extends FormRequest
{
    use InteractsWithOwnedResources;

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Conversation::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
            'model' => ['nullable', 'string', 'max:120', new AllowedAgentModel],
            'attachment_ids' => ['nullable', 'array', 'max:5'],
            'attachment_ids.*' => ['uuid', $this->ownedPendingAttachmentRule()],
        ];
    }
}
