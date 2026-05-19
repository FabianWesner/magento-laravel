<?php

namespace App\Modernization\Config;

final readonly class StoreView
{
    public function __construct(
        public int $id,
        public string $code,
        public WebsiteScope $website,
        public string $locale,
        public string $currency,
        public string $baseUrl,
    ) {}
}
