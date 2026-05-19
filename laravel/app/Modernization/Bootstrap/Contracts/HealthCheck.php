<?php

namespace App\Modernization\Bootstrap\Contracts;

interface HealthCheck
{
    public function name(): string;

    /**
     * @return array<string, mixed>
     */
    public function report(): array;
}
