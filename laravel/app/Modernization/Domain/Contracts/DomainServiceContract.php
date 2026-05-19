<?php

namespace App\Modernization\Domain\Contracts;

interface DomainServiceContract
{
    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function snapshot(string $key, array $filters = []): array;
}
