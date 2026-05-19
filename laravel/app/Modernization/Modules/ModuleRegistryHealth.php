<?php

namespace App\Modernization\Modules;

use App\Modernization\Bootstrap\Contracts\HealthCheck;
use App\Modernization\Modules\Contracts\ModuleRegistryContract;

final readonly class ModuleRegistryHealth implements HealthCheck
{
    public function __construct(private ModuleRegistryContract $registry) {}

    public function name(): string
    {
        return 'module_registry';
    }

    /**
     * @return array{
     *     status: string,
     *     module_count: int,
     *     enabled_module_count: int,
     *     errors: list<string>
     * }
     */
    public function report(): array
    {
        return [
            'status' => $this->registry->healthy() ? 'ok' : 'error',
            'module_count' => count($this->registry->all()),
            'enabled_module_count' => count($this->registry->enabled()),
            'errors' => $this->registry->errors(),
        ];
    }
}
