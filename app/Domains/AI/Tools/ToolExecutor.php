<?php

declare(strict_types=1);

namespace App\Domains\AI\Tools;

use App\Domains\AI\Enums\ToolName;
use App\Domains\AI\Services\AttachmentResolver;
use App\Domains\Stores\Contracts\StorePort;
use App\Domains\Stores\Exceptions\StoreException;
use App\Domains\Stores\Models\StoreConnection;

final class ToolExecutor
{
    public function __construct(
        private readonly MirrorToolReader $mirrorReader,
        private readonly AttachmentResolver $attachments,
    ) {}

    /**
     * @param  array<string, mixed>  $args
     * @return array{ok: bool, data?: mixed, error?: string}
     */
    public function execute(
        StoreConnection $store,
        ?StorePort $storePort,
        string $accountId,
        ToolName $tool,
        array $args,
    ): array {
        unset($args['_tool_call_id']);

        try {
            if (! $tool->isWrite()) {
                $data = $this->mirrorReader->read($store, $tool->value, $args);

                return ['ok' => true, 'data' => $this->truncate($data)];
            }

            if ($storePort === null) {
                throw new \RuntimeException('Store adapter is not available for write tools.');
            }

            $result = match ($tool) {
                ToolName::UpdateOrder => $storePort->updateOrder(
                    $args['external_id'],
                    array_filter([
                        'status' => $args['status'] ?? null,
                        'note' => $args['note'] ?? null,
                        'tags' => $args['tags'] ?? null,
                        'tracking_number' => $args['tracking_number'] ?? null,
                        'tracking_company' => $args['tracking_company'] ?? null,
                        'shipping_address' => $args['shipping_address'] ?? null,
                    ], fn ($value) => $value !== null),
                ),
                ToolName::FulfillOrder => $storePort->fulfillOrder(
                    $args['external_id'],
                    $args['tracking_number'] ?? null,
                ),
                ToolName::RefundOrder => $storePort->refundOrder(
                    $args['external_id'],
                    isset($args['amount']) ? (int) round(((float) $args['amount']) * 100) : null,
                ),
                ToolName::TagOrder => $storePort->tagOrder(
                    $args['external_id'],
                    $args['tags'],
                ),
                ToolName::CancelOrder => $storePort->cancelOrder(
                    $args['external_id'],
                    $args['reason'] ?? null,
                ),
                ToolName::CreateProduct => $storePort->createProduct(array_filter([
                    'title' => $args['title'],
                    'description' => $args['description'] ?? null,
                    'status' => $args['status'] ?? null,
                ], fn ($value) => $value !== null)),
                ToolName::UpdateProduct => $storePort->updateProduct(
                    $args['external_id'],
                    array_filter([
                        'title' => $args['title'] ?? null,
                        'description' => $args['description'] ?? null,
                        'status' => $args['status'] ?? null,
                        'images' => isset($args['image_attachment_ids'])
                            ? $this->attachments->resolveForStore($accountId, $args['image_attachment_ids'])
                            : null,
                    ], fn ($value) => $value !== null),
                ),
                ToolName::DeleteProduct => (function () use ($storePort, $args): array {
                    $storePort->deleteProduct($args['external_id']);

                    return ['deleted' => true, 'external_id' => $args['external_id']];
                })(),
                ToolName::UpdateInventory => (function () use ($storePort, $args): array {
                    $storePort->updateInventory(
                        $args['variant_external_id'],
                        (int) $args['quantity'],
                    );

                    return [
                        'variant_external_id' => $args['variant_external_id'],
                        'quantity' => (int) $args['quantity'],
                    ];
                })(),
                ToolName::TagCustomer => $storePort->tagCustomer(
                    $args['external_id'],
                    $args['tags'],
                ),
                default => throw new \InvalidArgumentException("Write tool [{$tool->value}] is not executable."),
            };

            if (isset($args['image_attachment_ids']) && $tool === ToolName::UpdateProduct) {
                $this->attachments->markConsumed($accountId, $args['image_attachment_ids']);
            }

            return ['ok' => true, 'data' => $this->serializeWriteResult($result)];
        } catch (StoreException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function serializeWriteResult(mixed $result): mixed
    {
        if ($result === null) {
            return ['success' => true];
        }

        if (is_object($result) && property_exists($result, 'externalId')) {
            return [
                'external_id' => $result->externalId,
                'title' => $result->title ?? null,
                'status' => $result->status ?? null,
            ];
        }

        return $result;
    }

    private function truncate(mixed $data): mixed
    {
        $encoded = json_encode($data);

        if ($encoded === false) {
            return $data;
        }

        $max = (int) config('agent.max_tool_result_chars', 8000);

        if (strlen($encoded) <= $max) {
            return $data;
        }

        return [
            'truncated' => true,
            'preview' => mb_substr($encoded, 0, $max).'…',
        ];
    }
}
