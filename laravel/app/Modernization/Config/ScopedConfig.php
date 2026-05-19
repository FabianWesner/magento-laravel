<?php

namespace App\Modernization\Config;

use App\Modernization\Config\Contracts\ConfigRepositoryContract;

final readonly class ScopedConfig
{
    public function __construct(
        private ConfigRepositoryContract $repository,
        private ConfigCache $cache,
        private EnvOverride $envOverride,
        private SecretConfig $secretConfig,
    ) {}

    public function get(string $path, ?StoreView $storeView = null): ?string
    {
        $override = $this->envOverride->get($path);
        if ($override !== null) {
            return $override;
        }

        $candidates = $this->candidates($storeView);

        foreach ($candidates as [$scope, $scopeId]) {
            $cached = $this->cache->get($path, $scope, $scopeId);
            if ($cached !== null) {
                return $this->secretConfig->decrypt($path, $cached);
            }

            $value = $this->repository->get($path, $scope, $scopeId);
            if ($value !== null) {
                $this->cache->put($path, $scope, $scopeId, $value->value);

                return $this->secretConfig->decrypt($path, $value->value);
            }
        }

        return null;
    }

    /**
     * @return array{locale: ?string, currency: ?string, base_url: ?string}
     */
    public function storeProfile(StoreView $storeView): array
    {
        return [
            'locale' => $this->get('general/locale/code', $storeView),
            'currency' => $this->get('currency/options/base', $storeView),
            'base_url' => $this->get('web/unsecure/base_url', $storeView),
        ];
    }

    /**
     * @return list<array{0: ConfigScope, 1: int}>
     */
    private function candidates(?StoreView $storeView): array
    {
        if ($storeView === null) {
            return [[ConfigScope::Default, 0]];
        }

        return [
            [ConfigScope::Store, $storeView->id],
            [ConfigScope::Website, $storeView->website->id],
            [ConfigScope::Default, 0],
        ];
    }
}
