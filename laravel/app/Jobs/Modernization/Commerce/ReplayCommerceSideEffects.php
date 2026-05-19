<?php

namespace App\Jobs\Modernization\Commerce;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ReplayCommerceSideEffects implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public readonly string $featureKey,
        public readonly array $snapshot,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Commerce side effect replay queued', [
            'feature_key' => $this->featureKey,
            'side effect' => true,
            'recovery' => true,
            'rollback' => $this->snapshot['rollback'] ?? 'legacy_runtime_fallback',
            'stale' => $this->snapshot['stale'] ?? false,
        ]);
    }
}
