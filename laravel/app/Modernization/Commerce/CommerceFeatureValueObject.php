<?php

namespace App\Modernization\Commerce;

class CommerceFeatureValueObject
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly CommerceFeature $feature,
        public readonly array $payload,
        public readonly string $rollback,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'feature' => $this->feature->toArray(),
            'payload' => $this->payload,
            'rollback' => $this->rollback,
        ];
    }
}
