<?php

namespace App\Modernization\Bootstrap\Health;

use App\Modernization\Bootstrap\Contracts\HealthCheck;
use Illuminate\Database\DatabaseManager;
use Throwable;

final readonly class DatabaseHealth implements HealthCheck
{
    public function __construct(private DatabaseManager $database) {}

    public function name(): string
    {
        return 'database';
    }

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        try {
            $this->database->connection()->selectOne('select 1 as ok');

            return [
                'status' => 'ok',
                'connection' => $this->database->getDefaultConnection(),
            ];
        } catch (Throwable $throwable) {
            return [
                'status' => 'error',
                'connection' => $this->database->getDefaultConnection(),
                'message' => $throwable->getMessage(),
            ];
        }
    }
}
