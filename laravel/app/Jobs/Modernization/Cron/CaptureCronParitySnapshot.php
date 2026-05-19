<?php

namespace App\Jobs\Modernization\Cron;

use DateTimeImmutable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CaptureCronParitySnapshot implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * @var list<int>
     */
    public array $backoff = [60, 300, 900];

    public int $tries = 3;

    public int $uniqueFor = 3600;

    /**
     * @param  list<string>  $featureIds
     */
    public function __construct(public array $featureIds) {}

    public function uniqueId(): string
    {
        return 'modernization-cron-parity-snapshot';
    }

    public function retryUntil(): DateTimeImmutable
    {
        return new DateTimeImmutable('+30 minutes');
    }

    public function handle(): void
    {
        Log::info('Cron parity report table snapshot requested', [
            'feature_ids' => $this->featureIds,
            'feature_count' => count($this->featureIds),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Cron parity report table snapshot failed', [
            'feature_ids' => $this->featureIds,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
