<?php

namespace App\Modernization\Cron;

use Illuminate\Support\Str;

class CronJobCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $jobs = config('cron_jobs.jobs', []);

        if (! is_array($jobs)) {
            return [];
        }

        return collect($jobs)
            ->filter(fn (mixed $job): bool => is_array($job))
            ->map(fn (array $job): array => $this->decorate($job))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $filters
     * @return list<array<string, mixed>>
     */
    public function filtered(array $filters): array
    {
        $query = Str::lower(trim(Str::substr($filters['query'] ?? '', 0, 128)));
        $status = $filters['status'] ?? '';
        $decision = $filters['decision'] ?? '';
        $domain = $filters['domain'] ?? '';

        return collect($this->all())
            ->filter(function (array $job) use ($query, $status, $decision, $domain): bool {
                if ($status !== '' && $job['status'] !== $status && ($status !== 'attention' || ! $job['needs_attention'])) {
                    return false;
                }

                if ($decision !== '' && $job['decision'] !== $decision) {
                    return false;
                }

                if ($domain !== '' && $job['domain'] !== $domain) {
                    return false;
                }

                if ($query === '') {
                    return true;
                }

                return Str::contains($job['haystack'], $query);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $jobs
     * @return array<string, int>
     */
    public function summary(array $jobs): array
    {
        return [
            'tracked' => count($jobs),
            'scheduled' => collect($jobs)->where('status', 'scheduled')->count(),
            'config_driven' => collect($jobs)->where('is_config_driven', true)->count(),
            'bridge' => collect($jobs)->where('decision', 'bridge')->count(),
            'attention' => collect($jobs)->where('needs_attention', true)->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array<string, mixed>
     */
    private function decorate(array $job): array
    {
        $featureId = (string) ($job['feature_id'] ?? '');
        $decision = (string) ($job['decision'] ?? 'replace');
        $schedule = (string) ($job['schedule'] ?? '');
        $status = $this->statusFor($featureId, $decision, $schedule);
        $domain = $this->domainFor($featureId);

        $row = [
            'feature_id' => $featureId,
            'name' => (string) ($job['name'] ?? ''),
            'name_label' => Str::headline((string) ($job['name'] ?? '')),
            'schedule' => $schedule,
            'legacy_model' => (string) ($job['legacy_model'] ?? ''),
            'decision' => $decision,
            'decision_label' => Str::headline($decision),
            'status' => $status,
            'status_label' => Str::headline($status),
            'domain' => $domain,
            'domain_label' => Str::headline($domain),
            'risk' => $this->riskFor($featureId),
            'last_run_at' => $this->lastRunAt($featureId, $status),
            'next_run_at' => $this->nextRunAt($featureId, $schedule, $status),
            'operator_note' => $this->operatorNote($featureId, $status),
            'is_config_driven' => $schedule === 'config-driven',
            'needs_attention' => $status !== 'scheduled',
        ];

        $row['haystack'] = Str::lower(implode(' ', array_filter([
            $row['feature_id'],
            $row['name'],
            $row['schedule'],
            $row['legacy_model'],
            $row['decision'],
            $row['status'],
            $row['domain'],
            $row['risk'],
            $row['operator_note'],
        ])));

        return $row;
    }

    private function statusFor(string $featureId, string $decision, string $schedule): string
    {
        if (in_array($featureId, ['CJ-014', 'CJ-021'], true)) {
            return 'blocked';
        }

        if ($decision === 'retire_or_bridge') {
            return 'decision_pending';
        }

        if ($decision === 'bridge') {
            return 'bridge';
        }

        if ($schedule === 'config-driven') {
            return 'config_pending';
        }

        return 'scheduled';
    }

    private function domainFor(string $featureId): string
    {
        return match (true) {
            in_array($featureId, ['CJ-007', 'CJ-008', 'CJ-009', 'CJ-010', 'CJ-011', 'CJ-015', 'CJ-020'], true) => 'reports',
            in_array($featureId, ['CJ-006', 'CJ-014', 'CJ-016', 'CJ-021'], true) => 'commerce',
            in_array($featureId, ['CJ-013', 'CJ-017', 'CJ-018', 'CJ-019', 'CJ-022'], true) => 'communications',
            in_array($featureId, ['CJ-002', 'CJ-004', 'CJ-025'], true) => 'integrations',
            default => 'operations',
        };
    }

    private function riskFor(string $featureId): string
    {
        return in_array($featureId, ['CJ-014', 'CJ-021'], true) ? 'P0' : 'P1';
    }

    private function lastRunAt(string $featureId, string $status): string
    {
        if (in_array($status, ['bridge', 'config_pending', 'decision_pending'], true)) {
            return 'awaiting retained legacy evidence';
        }

        return match ($featureId) {
            'CJ-017' => '2026-05-19T14:20:00+02:00',
            'CJ-022' => '2026-05-19T14:25:00+02:00',
            'CJ-023', 'CJ-024' => '2026-05-19T14:30:00+02:00',
            default => '2026-05-19T02:00:00+02:00',
        };
    }

    private function nextRunAt(string $featureId, string $schedule, string $status): string
    {
        if ($status === 'blocked') {
            return 'blocked until fixture and parity approval';
        }

        if ($schedule === 'config-driven') {
            return 'requires scoped config schedule fixture';
        }

        return match ($featureId) {
            'CJ-017' => 'every minute',
            'CJ-022' => 'every five minutes',
            'CJ-023' => 'every thirty minutes',
            'CJ-024' => 'every ten minutes',
            default => 'next scheduled window',
        };
    }

    private function operatorNote(string $featureId, string $status): string
    {
        return match ($status) {
            'bridge' => 'Bridge the legacy runner until sandbox, restore, and rollback evidence exists.',
            'config_pending' => 'Config-driven schedule needs scoped config fixtures before the Laravel scheduler can own it.',
            'decision_pending' => 'Retain, bridge, or retire decision is still open for this legacy mobile notification job.',
            'blocked' => $featureId === 'CJ-014'
                ? 'Catalog rule application can change prices; keep it read-only until commerce fixtures are canonical.'
                : 'Price reindexing changes storefront price output; keep it read-only until stale-index parity is accepted.',
            default => 'Mapped as a Laravel replacement candidate with scheduler locking and reportable status.',
        };
    }
}
