<?php

namespace App\Modernization\Config;

use App\Modernization\Config\Contracts\ConfigRepositoryContract;
use Illuminate\Database\ConnectionInterface;

final readonly class ConfigRepository implements ConfigRepositoryContract
{
    public function __construct(private ConnectionInterface $connection) {}

    public function get(string $path, ConfigScope $scope, int $scopeId): ?ConfigValue
    {
        $row = $this->connection
            ->table('core_config_data')
            ->where('path', $path)
            ->where('scope', $scope->value)
            ->where('scope_id', $scopeId)
            ->first();

        if ($row === null) {
            return null;
        }

        return new ConfigValue(
            path: (string) $row->path,
            scope: $scope,
            scopeId: (int) $row->scope_id,
            value: (string) $row->value,
        );
    }

    public function upsert(string $path, ConfigScope $scope, int $scopeId, string $value): ConfigValue
    {
        $this->connection
            ->table('core_config_data')
            ->updateOrInsert(
                ['path' => $path, 'scope' => $scope->value, 'scope_id' => $scopeId],
                ['value' => $value],
            );

        return new ConfigValue($path, $scope, $scopeId, $value);
    }
}
