<?php

namespace App\Modernization\Modules\Contracts;

use App\Modernization\Modules\ModuleManifest;

interface ModuleRegistryContract
{
    /**
     * @return list<ModuleManifest>
     */
    public function all(): array;

    /**
     * @return list<ModuleManifest>
     */
    public function enabled(): array;

    public function find(string $id): ?ModuleManifest;

    /**
     * @return list<string>
     */
    public function errors(): array;

    public function healthy(): bool;

    /**
     * @return array{modules: list<array<string, mixed>>, errors: list<string>}
     */
    public function toArray(): array;
}
