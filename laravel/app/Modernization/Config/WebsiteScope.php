<?php

namespace App\Modernization\Config;

final readonly class WebsiteScope
{
    public function __construct(
        public int $id,
        public string $code,
    ) {}
}
