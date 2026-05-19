<?php

namespace App\Modernization\Integrations;

use App\Jobs\Modernization\Integrations\RecoverIntegrationOutage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IntegrationGateway
{
    public function __construct(private readonly IntegrationConfig $config) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function request(string $key, array $payload = []): IntegrationResponse
    {
        $endpoint = $this->config->adapter($key);

        Log::withContext([
            'integration' => $endpoint->key,
            'context' => $endpoint->context,
            'feature_ids' => $endpoint->featureIds,
            'sandbox' => $endpoint->sandbox,
            'config_path' => $endpoint->configPath,
        ]);

        try {
            $pendingRequest = Http::timeout($endpoint->timeoutSeconds)
                ->connectTimeout(1)
                ->retry($endpoint->retryAttempts, 100, throw: false);

            if ($endpoint->oauth && $endpoint->secretKey !== null) {
                $pendingRequest = $pendingRequest->withToken((string) config($endpoint->secretKey, 'sandbox-oauth-token'));
            }

            $response = match ($endpoint->method) {
                'POST' => $pendingRequest->post($endpoint->endpoint, $payload),
                default => $pendingRequest->get($endpoint->endpoint, $payload),
            };

            $result = IntegrationResponse::fromResponse($endpoint, $response);

            if ($response->failed()) {
                $this->recover($endpoint, $result, 'status_failure');
            } else {
                Log::info('Integration request completed', $result->toArray());
            }

            return $result;
        } catch (ConnectionException $exception) {
            $result = IntegrationResponse::failure($endpoint, $exception->getMessage(), [
                'integration timeout' => true,
            ]);

            $this->recover($endpoint, $result, 'connection_exception');

            return $result;
        }
    }

    private function recover(IntegrationEndpoint $endpoint, IntegrationResponse $result, string $reason): void
    {
        RecoverIntegrationOutage::dispatch($endpoint->key, $result->toArray(), $reason);

        Log::warning('Integration outage recovery dispatched', [
            'integration' => $endpoint->key,
            'reason' => $reason,
            'rollback' => $endpoint->rollback,
            'recover' => true,
            'idempotency_key' => "{$endpoint->key}:{$reason}",
        ]);
    }
}
