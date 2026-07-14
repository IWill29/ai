<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domains\Stores\Models\StoreConnection;
use App\Http\Requests\Concerns\InteractsWithOwnedResources;
use Illuminate\Foundation\Http\FormRequest;

final class DashboardFilterRequest extends FormRequest
{
    use InteractsWithOwnedResources;

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', StoreConnection::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'store_id' => ['nullable', 'uuid', $this->ownedStoreConnectionRule()],
            'range' => ['nullable', 'in:7d,30d,90d,month,custom'],
            'from' => ['required_if:range,custom', 'nullable', 'date'],
            'to' => ['required_if:range,custom', 'nullable', 'date', 'after_or_equal:from'],
        ];
    }

    /**
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    public function dateRange(): array
    {
        return match ($this->validated('range', '30d')) {
            '7d' => [now()->subDays(7)->toImmutable(), now()->toImmutable()],
            '30d' => [now()->subDays(30)->toImmutable(), now()->toImmutable()],
            '90d' => [now()->subDays(90)->toImmutable(), now()->toImmutable()],
            'month' => [now()->startOfMonth()->toImmutable(), now()->toImmutable()],
            'custom' => [
                new \DateTimeImmutable((string) $this->validated('from')),
                new \DateTimeImmutable((string) $this->validated('to')),
            ],
            default => throw new \InvalidArgumentException('Unknown range'),
        };
    }
}
