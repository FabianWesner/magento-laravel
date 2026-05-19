<?php

namespace App\Jobs\Modernization\Integrations;

use DateTimeImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecoverIntegrationOutage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public readonly string $integration,
        public readonly array $snapshot,
        public readonly string $reason,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    public function retryUntil(): DateTimeImmutable
    {
        return now()->addMinutes(10)->toDateTimeImmutable();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::warning('Recovering integration outage', [
            'integration' => $this->integration,
            'reason' => $this->reason,
            'rollback' => $this->snapshot['rollback'] ?? 'legacy_runtime_fallback',
            'recover' => true,
            'replay' => true,
            'idempotency_key' => $this->integration.':'.$this->reason,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Integration outage recovery failed', [
            'integration' => $this->integration,
            'reason' => $this->reason,
            'failed' => true,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
