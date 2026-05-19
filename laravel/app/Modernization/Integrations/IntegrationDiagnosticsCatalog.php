<?php

namespace App\Modernization\Integrations;

use App\Modernization\Api\LegacyApiContract;
use App\Modernization\Api\LegacyApiContractRepository;
use Illuminate\Support\Str;

class IntegrationDiagnosticsCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function rows(): array
    {
        return [
            ...$this->apiContractRows(),
            ...$this->integrationRows(),
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return list<array<string, mixed>>
     */
    public function filtered(array $filters): array
    {
        $query = Str::lower(trim(Str::substr($filters['query'] ?? '', 0, 128)));
        $status = $filters['status'] ?? '';
        $kind = $filters['kind'] ?? '';
        $featureId = $filters['feature_id'] ?? '';

        return collect($this->rows())
            ->filter(function (array $row) use ($query, $status, $kind, $featureId): bool {
                if ($status !== '' && $row['status'] !== $status && ($status !== 'attention' || ! $row['needs_attention'])) {
                    return false;
                }

                if ($kind !== '' && $row['kind'] !== $kind) {
                    return false;
                }

                if ($featureId !== '' && ! in_array($featureId, $row['feature_ids'], true)) {
                    return false;
                }

                if ($query === '') {
                    return true;
                }

                return Str::contains($row['haystack'], $query);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, int>
     */
    public function summary(array $rows): array
    {
        return [
            'api_contracts' => collect($rows)->where('kind', 'api_contract')->count(),
            'adapters' => collect($rows)->where('kind', 'integration_adapter')->count(),
            'sandbox_adapters' => collect($rows)->where('sandbox', true)->count(),
            'callbacks' => collect($rows)->filter(fn (array $row): bool => $row['has_callback'])->count(),
            'attention' => collect($rows)->where('needs_attention', true)->count(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function apiContractRows(): array
    {
        return collect(app(LegacyApiContractRepository::class)->all())
            ->map(fn (LegacyApiContract $contract): array => $this->apiContractRow($contract))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function integrationRows(): array
    {
        return collect(app(IntegrationConfig::class)->all())
            ->map(fn (IntegrationEndpoint $endpoint): array => $this->integrationRow($endpoint))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function apiContractRow(LegacyApiContract $contract): array
    {
        $row = [
            'kind' => 'api_contract',
            'kind_label' => 'API Contract',
            'key' => $contract->featureId,
            'title' => $contract->name,
            'context' => $contract->protocol,
            'feature_ids' => [$contract->featureId],
            'status' => $contract->status,
            'status_label' => Str::headline($contract->status),
            'summary' => "Legacy {$contract->protocol} surface requires response, auth, and error-format parity.",
            'endpoint' => $contract->legacyEndpoint,
            'method' => 'legacy',
            'config_path' => 'api_contracts.contracts',
            'secret_key' => $contract->auth,
            'providers' => $contract->roles,
            'retry' => 'contract fixture',
            'timeout' => 'contract fixture',
            'rollback' => 'legacy_runtime_fallback',
            'sandbox' => false,
            'has_callback' => false,
            'needs_attention' => true,
        ];

        $row['haystack'] = $this->haystack($row);

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function integrationRow(IntegrationEndpoint $endpoint): array
    {
        $status = $this->integrationStatus($endpoint);
        $row = [
            'kind' => 'integration_adapter',
            'kind_label' => 'Integration Adapter',
            'key' => $endpoint->key,
            'title' => Str::headline($endpoint->key),
            'context' => $endpoint->context,
            'feature_ids' => $endpoint->featureIds,
            'status' => $status,
            'status_label' => Str::headline($status),
            'summary' => $this->integrationSummary($endpoint, $status),
            'endpoint' => $endpoint->endpoint,
            'method' => $endpoint->method,
            'config_path' => $endpoint->configPath,
            'secret_key' => $endpoint->secretKey ?? 'not configured',
            'providers' => $endpoint->providers,
            'retry' => (string) $endpoint->retryAttempts,
            'timeout' => "{$endpoint->timeoutSeconds}s",
            'rollback' => $endpoint->rollback,
            'sandbox' => $endpoint->sandbox,
            'has_callback' => $endpoint->webhook !== null || $endpoint->oauth,
            'needs_attention' => $status !== 'sandbox-configured',
        ];

        $row['haystack'] = $this->haystack($row);

        return $row;
    }

    private function integrationStatus(IntegrationEndpoint $endpoint): string
    {
        if ($endpoint->oauth) {
            return 'oauth-required';
        }

        if ($endpoint->webhook !== null) {
            return 'callback-required';
        }

        if (! $endpoint->sandbox) {
            return 'mock-required';
        }

        return 'sandbox-configured';
    }

    private function integrationSummary(IntegrationEndpoint $endpoint, string $status): string
    {
        return match ($status) {
            'oauth-required' => 'OAuth token exchange and secret rotation require sandbox parity before cutover.',
            'callback-required' => 'Callback, webhook, return URL, and signature paths require retained payload comparison.',
            default => $endpoint->sandbox
                ? 'Sandbox adapter is configured with timeout, retry, rollback, and secret metadata.'
                : 'Adapter needs sandbox metadata before it can be verified.',
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function haystack(array $row): string
    {
        return Str::lower(implode(' ', array_filter([
            $row['kind_label'] ?? '',
            $row['key'] ?? '',
            $row['title'] ?? '',
            $row['context'] ?? '',
            implode(' ', $row['feature_ids'] ?? []),
            $row['status'] ?? '',
            $row['endpoint'] ?? '',
            $row['method'] ?? '',
            $row['config_path'] ?? '',
            $row['secret_key'] ?? '',
            implode(' ', $row['providers'] ?? []),
            $row['rollback'] ?? '',
        ])));
    }
}
