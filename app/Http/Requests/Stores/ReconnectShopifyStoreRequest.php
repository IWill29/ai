<?php

declare(strict_types=1);

namespace App\Http\Requests\Stores;

use App\Domains\Stores\Models\StoreConnection;
use Illuminate\Foundation\Http\FormRequest;

class ReconnectShopifyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var StoreConnection|null $connection */
        $connection = $this->route('storeConnection');

        return $connection !== null
            && $this->user()?->can('reconnect', $connection) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'access_token' => ['required', 'string', 'starts_with:shpat_'],
        ];
    }
}
