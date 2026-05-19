<?php

namespace App\Modernization\Integrations;

class IntegrationEndpoint
{
    /**
     * @param  list<string>  $featureIds
     * @param  list<string>  $providers
     */
    public function __construct(
        public readonly string $key,
        public readonly string $context,
        public readonly array $featureIds,
        public readonly string $endpoint,
        public readonly string $method,
        public readonly string $configPath,
        public readonly ?string $secretKey,
        public readonly bool $sandbox,
        public readonly int $retryAttempts,
        public readonly int $timeoutSeconds,
        public readonly string $rollback,
        public readonly array $providers,
        public readonly bool $oauth,
        public readonly ?string $webhook,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(string $key, array $data): self
    {
        return new self(
            key: $key,
            context: (string) $data['context'],
            featureIds: array_values($data['feature_ids'] ?? []),
            endpoint: (string) $data['endpoint'],
            method: strtoupper((string) ($data['method'] ?? 'GET')),
            configPath: (string) ($data['config_path'] ?? ''),
            secretKey: isset($data['secret_key']) ? (string) $data['secret_key'] : null,
            sandbox: (bool) ($data['sandbox'] ?? false),
            retryAttempts: (int) ($data['retry_attempts'] ?? config('integrations.defaults.retry_attempts', 2)),
            timeoutSeconds: (int) ($data['timeout_seconds'] ?? config('integrations.defaults.timeout_seconds', 2)),
            rollback: (string) ($data['rollback'] ?? config('integrations.defaults.rollback', 'legacy_runtime_fallback')),
            providers: array_values($data['providers'] ?? []),
            oauth: (bool) ($data['oauth'] ?? false),
            webhook: isset($data['webhook']) ? (string) $data['webhook'] : null,
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
            'feature_ids' => $this->featureIds,
            'endpoint' => $this->endpoint,
            'method' => $this->method,
            'config_path' => $this->configPath,
            'secret_key' => $this->secretKey,
            'sandbox' => $this->sandbox,
            'retry_attempts' => $this->retryAttempts,
            'timeout_seconds' => $this->timeoutSeconds,
            'rollback' => $this->rollback,
            'providers' => $this->providers,
            'oauth' => $this->oauth,
            'webhook' => $this->webhook,
        ];
    }
}
