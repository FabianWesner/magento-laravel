<?php

namespace App\Modernization\Bootstrap\Health;

use App\Modernization\Bootstrap\Contracts\HealthCheck;
use Illuminate\Contracts\Foundation\Application;

final readonly class BootstrapHealth implements HealthCheck
{
    public function __construct(private Application $app) {}

    public function name(): string
    {
        return 'bootstrap';
    }

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        return [
            'status' => 'ok',
            'environment' => $this->app->environment(),
            'base_path' => $this->app->basePath(),
            'php_version' => PHP_VERSION,
        ];
    }
}
