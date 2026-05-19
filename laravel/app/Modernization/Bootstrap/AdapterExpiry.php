<?php

namespace App\Modernization\Bootstrap;

final readonly class AdapterExpiry
{
    public function __construct(public string $removalPhase) {}
}
