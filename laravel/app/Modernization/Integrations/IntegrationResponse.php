<?php

namespace App\Modernization\Integrations;

use Illuminate\Http\Client\Response;

class IntegrationResponse
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $key,
        public readonly string $context,
        public readonly bool $ok,
        public readonly ?int $status,
        public readonly array $payload,
        public readonly ?string $error,
        public readonly int $retryAttempts,
        public readonly string $rollback,
        public readonly bool $outage,
    ) {}

    public static function fromResponse(IntegrationEndpoint $endpoint, Response $response): self
    {
        $payload = $response->json();

        return new self(
            key: $endpoint->key,
            context: $endpoint->context,
            ok: $response->successful(),
            status: $response->status(),
            payload: is_array($payload) ? $payload : ['body' => $response->body()],
            error: $response->failed() ? 'integration returned non-success status' : null,
            retryAttempts: $endpoint->retryAttempts,
            rollback: $endpoint->rollback,
            outage: $response->failed(),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function failure(IntegrationEndpoint $endpoint, string $error, array $payload = []): self
    {
        return new self(
            key: $endpoint->key,
            context: $endpoint->context,
            ok: false,
            status: null,
            payload: $payload,
            error: $error,
            retryAttempts: $endpoint->retryAttempts,
            rollback: $endpoint->rollback,
            outage: true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'context' => $this->context,
            'ok' => $this->ok,
            'status' => $this->status,
            'payload' => $this->payload,
            'error' => $this->error,
            'retry_attempts' => $this->retryAttempts,
            'rollback' => $this->rollback,
            'outage' => $this->outage,
        ];
    }
}
