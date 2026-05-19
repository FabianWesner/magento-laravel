<?php

namespace App\Modernization\Bootstrap\Health;

use App\Modernization\Bootstrap\Contracts\HealthCheck;
use Illuminate\Cache\CacheManager;

final readonly class CacheHealth implements HealthCheck
{
    public function __construct(private CacheManager $cache) {}

    public function name(): string
    {
        return 'cache';
    }

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        return [
            'status' => 'ok',
            'driver' => $this->cache->getDefaultDriver(),
        ];
    }
}
