<?php

namespace App\Modernization\Config;

final readonly class ConfigValue
{
    public function __construct(
        public string $path,
        public ConfigScope $scope,
        public int $scopeId,
        public string $value,
        public bool $inherited = false,
    ) {}
}
