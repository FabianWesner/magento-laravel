<?php

namespace App\Modernization\Config;

use Illuminate\Support\Facades\Cache;

final class ConfigCache
{
    public function get(string $path, ConfigScope $scope, int $scopeId): ?string
    {
        $value = Cache::get($this->key($path, $scope, $scopeId));

        return is_string($value) ? $value : null;
    }

    public function put(string $path, ConfigScope $scope, int $scopeId, string $value): void
    {
        Cache::put($this->key($path, $scope, $scopeId), $value, 300);
    }

    public function invalidate(string $path, ConfigScope $scope, int $scopeId): void
    {
        Cache::forget($this->key($path, $scope, $scopeId));
    }

    public function key(string $path, ConfigScope $scope, int $scopeId): string
    {
        return "scoped-config:{$scope->value}:{$scopeId}:{$path}";
    }
}
