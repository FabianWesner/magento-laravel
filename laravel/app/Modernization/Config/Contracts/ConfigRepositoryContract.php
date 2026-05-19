<?php

namespace App\Modernization\Config\Contracts;

use App\Modernization\Config\ConfigScope;
use App\Modernization\Config\ConfigValue;

interface ConfigRepositoryContract
{
    public function get(string $path, ConfigScope $scope, int $scopeId): ?ConfigValue;

    public function upsert(string $path, ConfigScope $scope, int $scopeId, string $value): ConfigValue;
}
