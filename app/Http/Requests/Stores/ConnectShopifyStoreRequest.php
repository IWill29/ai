<?php

declare(strict_types=1);

namespace App\Http\Requests\Stores;

use App\Domains\Stores\Models\StoreConnection;
use Illuminate\Foundation\Http\FormRequest;

class ConnectShopifyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StoreConnection::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'regex:/^[a-z0-9-]+\.myshopify\.com$/i'],
            'access_token' => ['required', 'string', 'starts_with:shpat_'],
            'name' => ['nullable', 'string', 'max:120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('domain') && is_string($this->input('domain'))) {
            $this->merge([
                'domain' => strtolower(trim($this->input('domain'))),
            ]);
        }
    }
}
