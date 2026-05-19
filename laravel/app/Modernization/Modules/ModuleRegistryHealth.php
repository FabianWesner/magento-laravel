<?php

namespace App\Modernization\Modules;

final readonly class ModuleRegistryHealth
{
    public function __construct(private ModuleRegistry $registry) {}

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
