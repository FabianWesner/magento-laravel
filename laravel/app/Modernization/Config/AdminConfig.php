<?php

namespace App\Modernization\Config;

use App\Modernization\Config\Contracts\ConfigRepositoryContract;
use Illuminate\Validation\ValidationException;

final readonly class AdminConfig
{
    public function __construct(
        private ConfigRepositoryContract $repository,
        private ConfigCache $cache,
        private SourceModel $sourceModel,
        private BackendModel $backendModel,
    ) {}

    public function save(string $path, string $value, ConfigScope $scope, int $scopeId, bool $inherited = false): ?ConfigValue
    {
        if ($inherited) {
            $this->cache->invalidate($path, $scope, $scopeId);

            return null;
        }

        if (! $this->sourceModel->allows($path, $value)) {
            throw ValidationException::withMessages([
                $path => "The selected system configuration source model value [{$value}] is invalid.",
            ]);
        }

        $configValue = $this->repository->upsert(
            $path,
            $scope,
            $scopeId,
            $this->backendModel->beforeSave($path, $value),
        );

        $this->cache->invalidate($path, $scope, $scopeId);

        return $configValue;
    }
}
