<?php

namespace App\Modernization\Bootstrap\Health;

use App\Modernization\Bootstrap\Contracts\HealthCheck;
use Illuminate\Session\SessionManager;

final readonly class SessionHealth implements HealthCheck
{
    public function __construct(private SessionManager $session) {}

    public function name(): string
    {
        return 'session';
    }

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        return [
            'status' => 'ok',
            'driver' => $this->session->getDefaultDriver(),
        ];
    }
}
