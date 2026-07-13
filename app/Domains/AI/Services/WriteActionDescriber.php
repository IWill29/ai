<?php

declare(strict_types=1);

namespace App\Domains\AI\Services;

use App\Domains\AI\Enums\ToolName;

final class WriteActionDescriber
{
    /** @param array<string, mixed> $args */
    public function describe(ToolName $tool, array $args): string
    {
        return match ($tool) {
            ToolName::UpdateProduct => $this->describeProductUpdate($args),
            ToolName::UpdateOrder => $this->describeOrderUpdate($args),
            default => ucfirst(str_replace('_', ' ', $tool->value)).' on '.($args['external_id'] ?? 'target'),
        };
    }

    /** @param array<string, mixed> $args */
    private function describeProductUpdate(array $args): string
    {
        $parts = ['Update product '.($args['external_id'] ?? '')];

        if (isset($args['title'])) {
            $parts[] = 'title → '.$args['title'];
        }

        if (isset($args['description'])) {
            $preview = mb_strlen((string) $args['description']) > 120
                ? mb_substr((string) $args['description'], 0, 120).'…'
                : (string) $args['description'];
            $parts[] = 'description → '.$preview;
        }

        if (isset($args['status'])) {
            $parts[] = 'status → '.$args['status'];
        }

        if (! empty($args['image_attachment_ids']) && is_array($args['image_attachment_ids'])) {
            $parts[] = 'add '.count($args['image_attachment_ids']).' image(s)';
        }

        return implode('; ', $parts);
    }

    /** @param array<string, mixed> $args */
    private function describeOrderUpdate(array $args): string
    {
        $parts = ['Update order '.($args['external_id'] ?? '')];

        foreach (['status', 'note', 'tracking_number', 'tracking_company'] as $field) {
            if (isset($args[$field])) {
                $parts[] = "{$field} → {$args[$field]}";
            }
        }

        if (isset($args['tags']) && is_array($args['tags'])) {
            $parts[] = 'tags → '.implode(', ', $args['tags']);
        }

        return implode('; ', $parts);
    }
}
