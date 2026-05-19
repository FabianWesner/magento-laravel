<?php

namespace App\Console\Commands;

use App\Jobs\Modernization\Cron\CaptureCronParitySnapshot;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('modernization:cron-status {--dispatch : Dispatch a queued report table snapshot parity job.}')]
#[Description('Show scheduler diagnostics/status for Magento cron modernization.')]
final class ModernizationCronStatusCommand extends Command
{
    public function handle(): int
    {
        $jobs = config('cron_jobs.jobs', []);
        if (! is_array($jobs)) {
            $this->error('Cron diagnostics configuration is invalid.');

            return self::FAILURE;
        }

        $featureIds = array_values(array_map(
            static fn (array $job): string => (string) $job['feature_id'],
            $jobs,
        ));

        $this->info('Modernization cron diagnostics status');
        $this->line('Tracked CJ jobs: '.count($jobs));

        foreach ($jobs as $job) {
            $this->line(sprintf(
                '%s | %s | %s | %s',
                $job['feature_id'],
                $job['name'],
                $job['schedule'],
                $job['decision'],
            ));
        }

        if ($this->option('dispatch')) {
            dispatch(new CaptureCronParitySnapshot($featureIds));
            $this->info('Cron parity snapshot job dispatched.');
        }

        return self::SUCCESS;
    }
}
