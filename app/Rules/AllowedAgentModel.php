<?php

declare(strict_types=1);

namespace App\Rules;

use App\Domains\AI\Exceptions\ModelNotAllowedException;
use App\Domains\AI\Services\ModelAllowList;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class AllowedAgentModel implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('The :attribute must be a valid model.');

            return;
        }

        try {
            app(ModelAllowList::class)->assertAllowed($value);
        } catch (ModelNotAllowedException) {
            $fail('The selected :attribute is not allowed.');
        }
    }
}
