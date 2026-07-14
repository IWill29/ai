<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Domains\AI\Contracts\AgentLlmPort;
use App\Domains\AI\Contracts\AgentService;
use App\Domains\AI\DTOs\LlmResponse;
use App\Domains\AI\DTOs\ToolCall;
use App\Domains\AI\Enums\StepStatus;
use App\Domains\AI\Services\DefaultAgentService;
use App\Domains\Chat\Models\ActionStep;
use App\Domains\Stores\Contracts\StoreAdapterFactory;
use App\Domains\Stores\Contracts\StorePort;
use App\Domains\Stores\DTOs\OrderDTO;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Testing\TestResponse;
use Mockery;
use Tests\Support\AgentTestData;

trait MocksAgentLlm
{
    /** @param list<class-string> $extra */
    protected function resetAgentContainer(array $extra = []): void
    {
        foreach (array_merge([
            AgentLlmPort::class,
            AgentService::class,
            DefaultAgentService::class,
        ], $extra) as $abstract) {
            $this->app->forgetInstance($abstract);
        }
    }

    protected function mockSimpleAnswerLlm(string $answer): void
    {
        $this->mock(AgentLlmPort::class, function ($mock) use ($answer): void {
            $mock->shouldReceive('stream')
                ->once()
                ->andReturnUsing(function (...$args) use ($answer) {
                    $model = $args[1];
                    $onDelta = $args[4];
                    $onDelta($answer);

                    return new LlmResponse(
                        content: $answer,
                        toolCalls: [],
                        finishReason: 'stop',
                        promptTokens: 10,
                        completionTokens: 5,
                        model: $model,
                    );
                });
        });
    }

    protected function mockFulfillOrderLlm(
        string $orderExternalId = AgentTestData::ORDER_1,
        string $callId = 'call_fulfill_1',
    ): void {
        $this->mock(AgentLlmPort::class, function ($mock) use ($orderExternalId, $callId): void {
            $mock->shouldReceive('stream')
                ->once()
                ->andReturn(new LlmResponse(
                    content: null,
                    toolCalls: [
                        new ToolCall(
                            id: $callId,
                            name: 'fulfill_order',
                            arguments: ['external_id' => $orderExternalId],
                        ),
                    ],
                    finishReason: 'tool_calls',
                    promptTokens: 15,
                    completionTokens: 3,
                    model: AgentTestData::DEFAULT_MODEL,
                ));
        });
    }

    protected function mockResumeAnswerLlm(string $answer = 'Done.'): void
    {
        $this->mock(AgentLlmPort::class, function ($mock) use ($answer): void {
            $mock->shouldReceive('stream')
                ->once()
                ->andReturnUsing(function (...$args) use ($answer) {
                    $onDelta = $args[4];
                    $onDelta($answer);

                    return new LlmResponse(
                        content: $answer,
                        toolCalls: [],
                        finishReason: 'stop',
                        promptTokens: 10,
                        completionTokens: 3,
                        model: $args[1],
                    );
                });
        });
    }

    protected function confirmFulfillment(
        User $user,
        ActionStep $step,
        string $orderExternalId,
        bool $expectAuditLog = false,
        string $orderNumber = '#1001',
    ): void {
        $this->bindFulfillOrderStorePort($orderExternalId, $orderNumber);

        $this->actingAs($user)->postJson(route('action-steps.confirm', $step), [
            'confirmed' => true,
        ])->assertOk();

        $step->refresh();
        $this->assertSame(StepStatus::Done->value, $step->status);

        if ($expectAuditLog) {
            $this->assertDatabaseHas('audit_logs', [
                'account_id' => $user->account_id,
                'action' => 'tool.fulfill_order',
            ]);
        }
    }

    protected function declineFulfillment(User $user, ActionStep $step): void
    {
        $this->mockStorePortDeclinesFulfill();

        $this->actingAs($user)->postJson(route('action-steps.confirm', $step), [
            'confirmed' => false,
        ])->assertOk();
    }

    protected function assertStreamRequiresConfirmation(TestResponse $response): string
    {
        $response->assertOk();
        $body = $response->streamedContent();
        $this->assertStringContainsString(AgentTestData::SSE_CONFIRMATION_REQUIRED, $body, $body);

        return $body;
    }

    protected function mockStorePortDeclinesFulfill(): void
    {
        $storePort = Mockery::mock(StorePort::class);
        $storePort->shouldNotReceive('fulfillOrder');
        $factory = Mockery::mock(StoreAdapterFactory::class);
        $factory->shouldReceive('for')->andReturn($storePort);
        $this->instance(StoreAdapterFactory::class, $factory);
    }

    private function bindFulfillOrderStorePort(string $orderExternalId, string $orderNumber): void
    {
        $storePort = Mockery::mock(StorePort::class);
        $storePort->shouldReceive('fulfillOrder')
            ->once()
            ->with($orderExternalId, null)
            ->andReturn($this->fulfilledOrderDto($orderExternalId, $orderNumber));

        $factory = Mockery::mock(StoreAdapterFactory::class);
        $factory->shouldReceive('for')->andReturn($storePort);
        $this->instance(StoreAdapterFactory::class, $factory);
    }

    private function fulfilledOrderDto(string $externalId, string $orderNumber): OrderDTO
    {
        return new OrderDTO(
            externalId: $externalId,
            orderNumber: $orderNumber,
            financialStatus: 'paid',
            fulfillmentStatus: 'fulfilled',
            totalPriceMinor: 2500,
            currency: 'EUR',
            customerExternalId: null,
            lineItems: [],
            placedAt: new DateTimeImmutable,
        );
    }
}
