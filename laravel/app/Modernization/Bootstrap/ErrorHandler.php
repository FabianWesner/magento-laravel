<?php

namespace App\Modernization\Bootstrap;

use Throwable;

final readonly class ErrorHandler
{
    /**
     * @return array<string, string>
     */
    public function context(Throwable $throwable): array
    {
        return [
            'exception' => $throwable::class,
            'message' => $throwable->getMessage(),
        ];
    }
}
