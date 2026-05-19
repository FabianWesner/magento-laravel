<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Cron\CronJobCatalog;
use App\Policies\Modernization\Cron\CronPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class CronJobsWorkbench extends Component
{
    public string $query = '';

    public string $status = '';

    public string $decision = '';

    public string $domain = '';

    public string $section = 'jobs';

    public string $role = 'scheduler';

    public function setSection(string $section): void
    {
        if (! in_array($section, ['jobs', 'config', 'reports', 'problems'], true)) {
            return;
        }

        $this->section = $section;
    }

    public function clearFilters(): void
    {
        $this->query = '';
        $this->status = '';
        $this->decision = '';
        $this->domain = '';
        $this->section = 'jobs';
    }

    public function render(CronJobCatalog $catalog, CronPolicy $policy): View
    {
        $this->status = $this->allowedStatus();
        $this->decision = $this->allowedDecision();
        $this->domain = $this->allowedDomain();
        $this->section = $this->allowedSection();

        $canViewCronJobs = $this->canViewCronJobs($policy);
        $jobs = $canViewCronJobs ? $catalog->filtered($this->filters()) : [];
        $configRows = $this->configRows($jobs);
        $reportRows = $this->reportRows($jobs);
        $problemRows = $this->problemRows($jobs);

        return view('livewire.cron-jobs-workbench', [
            'canViewCronJobs' => $canViewCronJobs,
            'jobs' => $jobs,
            'configRows' => $configRows,
            'reportRows' => $reportRows,
            'problemRows' => $problemRows,
            'currentRows' => $this->currentRows($jobs, $configRows, $reportRows, $problemRows),
            'summary' => $catalog->summary($jobs),
            'activeFilters' => $this->activeFilters(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function filters(): array
    {
        return array_filter([
            'query' => trim(Str::substr($this->query, 0, 128)),
            'status' => $this->allowedStatus(),
            'decision' => $this->allowedDecision(),
            'domain' => $this->allowedDomain(),
        ], fn (string $value): bool => $value !== '');
    }

    /**
     * @param  list<array<string, mixed>>  $jobs
     * @return list<array<string, mixed>>
     */
    private function configRows(array $jobs): array
    {
        return collect($jobs)
            ->where('is_config_driven', true)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $jobs
     * @return list<array<string, mixed>>
     */
    private function reportRows(array $jobs): array
    {
        return collect($jobs)
            ->where('domain', 'reports')
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $jobs
     * @return list<array<string, mixed>>
     */
    private function problemRows(array $jobs): array
    {
        return collect($jobs)
            ->where('needs_attention', true)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $jobs
     * @param  list<array<string, mixed>>  $configRows
     * @param  list<array<string, mixed>>  $reportRows
     * @param  list<array<string, mixed>>  $problemRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $jobs, array $configRows, array $reportRows, array $problemRows): array
    {
        return match ($this->allowedSection()) {
            'config' => $configRows,
            'reports' => $reportRows,
            'problems' => $problemRows,
            default => $jobs,
        };
    }

    /**
     * @return array<string, string>
     */
    private function activeFilters(): array
    {
        return array_filter([
            'query' => trim(Str::substr($this->query, 0, 128)),
            'status' => $this->allowedStatus(),
            'decision' => $this->allowedDecision(),
            'domain' => $this->allowedDomain(),
        ], fn (string $value): bool => $value !== '');
    }

    private function allowedStatus(): string
    {
        return in_array($this->status, ['', 'scheduled', 'bridge', 'config_pending', 'decision_pending', 'blocked', 'attention'], true) ? $this->status : '';
    }

    private function allowedDecision(): string
    {
        return in_array($this->decision, ['', 'replace', 'bridge', 'retire_or_bridge'], true) ? $this->decision : '';
    }

    private function allowedDomain(): string
    {
        return in_array($this->domain, ['', 'operations', 'integrations', 'reports', 'commerce', 'communications'], true) ? $this->domain : '';
    }

    private function allowedSection(): string
    {
        return in_array($this->section, ['jobs', 'config', 'reports', 'problems'], true) ? $this->section : 'jobs';
    }

    private function canViewCronJobs(CronPolicy $policy): bool
    {
        if (! app()->environment(['local', 'testing'])) {
            return false;
        }

        return $policy->view($this->fixtureUser());
    }

    private function fixtureUser(): User
    {
        $user = new User;
        $user->forceFill([
            'id' => 930,
            'name' => "{$this->role} cron fixture",
            'email' => "{$this->role}-cron@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }
}
