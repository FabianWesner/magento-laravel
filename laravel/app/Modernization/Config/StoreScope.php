<?php

namespace App\Modernization\Config;

final readonly class StoreScope
{
    public function __construct(
        public int $id,
        public string $code,
        public WebsiteScope $website,
    ) {}
}
