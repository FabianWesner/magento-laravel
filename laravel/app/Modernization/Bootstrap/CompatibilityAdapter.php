<?php

namespace App\Modernization\Bootstrap;

final readonly class CompatibilityAdapter
{
    public function __construct(
        public string $owner,
        public AdapterExpiry $expiry,
        public bool $finalReleaseRuntimeDependency,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return [
            'owner' => $this->owner,
            'expiry_phase' => $this->expiry->removalPhase,
            'final_release_runtime_dependency' => $this->finalReleaseRuntimeDependency,
            'no final-release runtime dependency' => ! $this->finalReleaseRuntimeDependency,
        ];
    }
}
