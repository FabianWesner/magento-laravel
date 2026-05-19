<?php

namespace App\Modernization\Bootstrap;

final readonly class Observability
{
    /**
     * @return array<string, string>
     */
    public function context(string $component): array
    {
        return [
            'modernization_component' => $component,
            'runtime' => 'laravel',
        ];
    }
}
