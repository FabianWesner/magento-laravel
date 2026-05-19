<?php

namespace App\Modernization\Domain;

class DomainFeature
{
    /**
     * @param  list<string>  $featureIds
     * @param  list<string>  $states
     */
    public function __construct(
        public readonly string $key,
        public readonly string $context,
        public readonly string $label,
        public readonly array $featureIds,
        public readonly array $states,
        public readonly bool $storeScoped,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'context' => $this->context,
            'label' => $this->label,
            'feature_ids' => $this->featureIds,
            'states' => $this->states,
            'store_scope' => $this->storeScoped,
        ];
    }
}
