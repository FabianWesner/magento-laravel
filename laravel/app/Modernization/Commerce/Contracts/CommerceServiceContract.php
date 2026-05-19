<?php

namespace App\Modernization\Commerce\Contracts;

interface CommerceServiceContract
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function snapshot(string $featureKey, array $payload = []): array;
}
