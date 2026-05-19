<?php

namespace Tests\Feature;

use App\Jobs\Modernization\Cron\CaptureCronParitySnapshot;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CronJobSchedulerTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const CRON_FEATURE_IDS = [
        'CJ-001',
        'CJ-002',
        'CJ-003',
        'CJ-004',
        'CJ-005',
        'CJ-006',
        'CJ-007',
        'CJ-008',
        'CJ-009',
        'CJ-010',
        'CJ-011',
        'CJ-012',
        'CJ-013',
        'CJ-014',
        'CJ-015',
        'CJ-016',
        'CJ-017',
        'CJ-018',
        'CJ-019',
        'CJ-020',
        'CJ-021',
        'CJ-022',
        'CJ-023',
        'CJ-024',
        'CJ-025',
    ];

    public function test_scheduler_registers_cron_diagnostics_command_with_locking_controls(): void
    {
        $events = collect($this->app->make(Schedule::class)->events());
        $commands = $events->map(fn ($event): string => (string) $event->command)->implode("\n");
        $scheduleDefinition = file_get_contents(base_path('routes/console.php'));

        $this->assertStringContainsString('modernization:cron-status --dispatch', $commands);
        $this->assertIsString($scheduleDefinition);
        $this->assertStringContainsString('withoutOverlapping', $scheduleDefinition);
        $this->assertStringContainsString('onOneServer', $scheduleDefinition);

        $this->artisan('schedule:list')
            ->expectsOutputToContain('modernization:cron-status --dispatch')
            ->assertSuccessful();
    }

    public function test_cron_status_command_reports_jobs_and_dispatches_queue_snapshot(): void
    {
        Bus::fake();

        $this->artisan('modernization:cron-status', ['--dispatch' => true])
            ->expectsOutput('Modernization cron diagnostics status')
            ->expectsOutput('Tracked CJ jobs: 25')
            ->expectsOutput('CJ-001 | scheduled backup | config-driven | bridge')
            ->expectsOutput('CJ-025 | generate sitemaps | config-driven | replace')
            ->expectsOutput('Cron parity snapshot job dispatched.')
            ->assertSuccessful();

        Bus::assertDispatched(CaptureCronParitySnapshot::class, function (CaptureCronParitySnapshot $job): bool {
            return $job->featureIds === self::CRON_FEATURE_IDS;
        });
    }

    public function test_cron_snapshot_job_has_retry_failure_logging_and_idempotency_policy(): void
    {
        Log::spy();

        $job = new CaptureCronParitySnapshot(['CJ-001', 'CJ-002']);

        $this->assertSame([60, 300, 900], $job->backoff);
        $this->assertSame(3, $job->tries);
        $this->assertSame(3600, $job->uniqueFor);
        $this->assertSame('modernization-cron-parity-snapshot', $job->uniqueId());

        $job->handle();
        $job->failed(new \RuntimeException('snapshot failed'));

        Log::shouldHaveReceived('info')->once()->with('Cron parity report table snapshot requested', [
            'feature_ids' => ['CJ-001', 'CJ-002'],
            'feature_count' => 2,
        ]);
        Log::shouldHaveReceived('error')->once()->with('Cron parity report table snapshot failed', [
            'feature_ids' => ['CJ-001', 'CJ-002'],
            'exception' => 'snapshot failed',
        ]);
    }

    public function test_all_cj_feature_ids_have_scheduler_mapping_and_report_table_snapshot_expectation(): void
    {
        $jobs = config('cron_jobs.jobs');

        $this->assertIsArray($jobs);
        $this->assertSame(self::CRON_FEATURE_IDS, array_column($jobs, 'feature_id'), 'report table snapshot aggregate coverage must stay one row per CJ feature ID');

        foreach ($jobs as $job) {
            $this->assertArrayHasKey('schedule', $job);
            $this->assertArrayHasKey('legacy_model', $job);
            $this->assertArrayHasKey('decision', $job);
        }
    }
}
