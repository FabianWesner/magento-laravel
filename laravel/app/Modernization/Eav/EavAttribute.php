<?php

namespace App\Modernization\Eav;

final readonly class EavAttribute
{
    public function __construct(
        public int $attributeId,
        public int $entityTypeId,
        public string $code,
        public string $backendType,
    ) {}
}
