<?php

declare(strict_types=1);

namespace App\Domains\Stores\Adapters\Shopify;

use App\Domains\Stores\Exceptions\InvalidCredentialsException;
use App\Domains\Stores\Exceptions\RateLimitException;
use App\Domains\Stores\Exceptions\StoreApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

final class ShopifyClient
{
    private readonly ShopifyCircuitBreaker $circuitBreaker;

    public function __construct(
        private readonly string $shopDomain,
        private readonly string $accessToken,
        private readonly ?string $connectionId = null,
    ) {
        $this->circuitBreaker = new ShopifyCircuitBreaker($connectionId);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    public function graphql(string $query, array $variables = []): array
    {
        $maxThrottleRetries = (int) config('shopify.throttle_max_retries', 2);
        $attempt = 0;

        while (true) {
            try {
                $this->circuitBreaker->assertClosed();

                try {
                    $response = $this->request()->post($this->endpoint(), [
                        'query' => $query,
                        'variables' => $variables,
                    ]);
                } catch (RequestException $exception) {
                    $status = $exception->response->status();

                    if ($status === 401) {
                        $this->circuitBreaker->recordFailure();

                        throw new InvalidCredentialsException('Shopify rejected the access token.');
                    }

                    $this->circuitBreaker->recordFailure();

                    throw new StoreApiException("Shopify HTTP {$status}");
                }

                if ($response->status() === 401) {
                    $this->circuitBreaker->recordFailure();

                    throw new InvalidCredentialsException('Shopify rejected the access token.');
                }

                if ($response->failed()) {
                    $this->circuitBreaker->recordFailure();

                    throw new StoreApiException("Shopify HTTP {$response->status()}");
                }

                /** @var array<string, mixed> $body */
                $body = $response->json();

                $this->guardAgainstThrottle($body);

                if (! empty($body['errors'])) {
                    $this->circuitBreaker->recordFailure();

                    $message = is_array($body['errors'][0] ?? null)
                        ? ($body['errors'][0]['message'] ?? 'Shopify GraphQL error')
                        : 'Shopify GraphQL error';

                    throw new StoreApiException((string) $message);
                }

                $this->circuitBreaker->recordSuccess();

                /** @var array<string, mixed> */
                return $body['data'] ?? [];
            } catch (RateLimitException $exception) {
                if ($attempt >= $maxThrottleRetries) {
                    $this->circuitBreaker->recordFailure();

                    throw $exception;
                }

                $attempt++;
                usleep(($exception->retryAfterSeconds ?? 2) * 1_000_000);
            } catch (InvalidCredentialsException|StoreApiException $exception) {
                throw $exception;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $target
     */
    public function uploadToStagedTarget(array $target, string $localPath): void
    {
        if (! is_readable($localPath)) {
            throw new StoreApiException('Product image file is not readable.');
        }

        $url = $target['url'] ?? null;

        if (! is_string($url) || $url === '') {
            throw new StoreApiException('Shopify staged upload target is missing a URL.');
        }

        /** @var array<int, array{name?: string, value?: string}> $parameters */
        $parameters = $target['parameters'] ?? [];

        $multipart = [];

        foreach ($parameters as $parameter) {
            $name = $parameter['name'] ?? null;
            $value = $parameter['value'] ?? null;

            if (is_string($name) && $name !== '') {
                $multipart[] = [
                    'name' => $name,
                    'contents' => is_string($value) ? $value : '',
                ];
            }
        }

        $multipart[] = [
            'name' => 'file',
            'contents' => fopen($localPath, 'rb'),
            'filename' => basename($localPath),
        ];

        $response = Http::timeout(config('shopify.timeout'))
            ->asMultipart()
            ->post($url, $multipart);

        if ($response->failed()) {
            throw new StoreApiException("Shopify staged upload failed with HTTP {$response->status()}");
        }
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])
            ->timeout((int) config('shopify.timeout', 15))
            ->retry(
                (int) config('shopify.max_retries', 3),
                200,
                fn ($exception) => $exception instanceof ConnectionException,
            );
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function guardAgainstThrottle(array $body): void
    {
        $code = $body['errors'][0]['extensions']['code'] ?? null;

        if ($code === 'THROTTLED' || $code === 'MAX_COST_EXCEEDED') {
            throw new RateLimitException(
                retryAfterSeconds: 2,
                message: 'Shopify GraphQL rate limit / query cost exceeded',
            );
        }
    }

    private function endpoint(): string
    {
        $version = config('shopify.api_version');

        return "https://{$this->shopDomain}/admin/api/{$version}/graphql.json";
    }
}
