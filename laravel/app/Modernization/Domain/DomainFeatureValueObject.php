<?php

namespace App\Modernization\Domain;

class DomainFeatureValueObject
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly DomainFeature $feature,
        public readonly array $payload,
        public readonly ?string $storeView,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'feature' => $this->feature->toArray(),
            'payload' => $this->payload,
            'store_view' => $this->storeView,
        ];
    }
}
