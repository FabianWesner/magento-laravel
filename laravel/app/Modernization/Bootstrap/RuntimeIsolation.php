<?php

namespace App\Modernization\Bootstrap;

final readonly class RuntimeIsolation
{
    public function __construct(
        private string $laravelBasePath,
        private string $legacyCorePath,
        private int $phpVersionId,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        return [
            'status' => $this->isolated() ? 'ok' : 'error',
            'laravel_base_path' => $this->laravelBasePath,
            'legacy_core_path' => $this->legacyCorePath,
            'php_version_id' => $this->phpVersionId,
            'separate_php_runtime' => $this->phpVersionId >= 80500,
            'legacy_php74_only_for_baseline_smoke' => true,
            'not_loaded_inside_legacy_process' => ! str_contains($this->laravelBasePath, '/core/magento-1.9.4.5'),
        ];
    }

    public function isolated(): bool
    {
        return $this->phpVersionId >= 80500
            && is_file($this->legacyCorePath.'/app/Mage.php')
            && ! str_contains($this->laravelBasePath, '/core/magento-1.9.4.5');
    }
}
