<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Domains\Chat\Models\ActionStep;
use Illuminate\Foundation\Http\FormRequest;

final class ConfirmActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ActionStep|null $step */
        $step = $this->route('actionStep');

        return $step !== null && ($this->user()?->can('update', $step) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'confirmed' => ['required', 'boolean'],
        ];
    }
}
