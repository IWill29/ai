<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DashboardFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'store_id' => [
                'nullable',
                'uuid',
                Rule::exists('store_connections', 'id')->where(
                    fn ($query) => $query->where('account_id', $this->user()?->account_id),
                ),
            ],
            'range' => ['nullable', 'in:7d,30d,90d,month,custom'],
            'from' => ['required_if:range,custom', 'date'],
            'to' => ['required_if:range,custom', 'date', 'after_or_equal:from'],
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
