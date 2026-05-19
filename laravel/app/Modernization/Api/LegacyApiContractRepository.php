<?php

namespace App\Modernization\Api;

final class LegacyApiContractRepository
{
    /**
     * @param  array{protocol?: string, role?: string, feature_id?: string}  $filters
     * @return list<LegacyApiContract>
     */
    public function all(array $filters = []): array
    {
        return array_values(array_filter(
            $this->contracts(),
            fn (LegacyApiContract $contract): bool => $this->matches($contract, $filters),
        ));
    }

    public function find(string $featureId): ?LegacyApiContract
    {
        foreach ($this->contracts() as $contract) {
            if ($contract->featureId === $featureId) {
                return $contract;
            }
        }

        return null;
    }

    /**
     * @return list<LegacyApiContract>
     */
    private function contracts(): array
    {
        $contracts = config('api_contracts.contracts', []);
        if (! is_array($contracts)) {
            return [];
        }

        return array_map(
            static fn (array $contract): LegacyApiContract => new LegacyApiContract(
                featureId: (string) $contract['feature_id'],
                name: (string) $contract['name'],
                protocol: (string) $contract['protocol'],
                auth: (string) $contract['auth'],
                roles: array_values(array_map('strval', $contract['roles'])),
                legacyEndpoint: (string) $contract['legacy_endpoint'],
                status: (string) $contract['status'],
            ),
            $contracts,
        );
    }

    /**
     * @param  array{protocol?: string, role?: string, feature_id?: string}  $filters
     */
    private function matches(LegacyApiContract $contract, array $filters): bool
    {
        if (($filters['protocol'] ?? null) !== null && $contract->protocol !== $filters['protocol']) {
            return false;
        }

        if (($filters['role'] ?? null) !== null && ! in_array($filters['role'], $contract->roles, true)) {
            return false;
        }

        if (($filters['feature_id'] ?? null) !== null && $contract->featureId !== $filters['feature_id']) {
            return false;
        }

        return true;
    }
}
