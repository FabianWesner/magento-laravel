<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Reports\ReportCatalog;
use App\Modernization\Reports\ReportDefinition;
use App\Modernization\Reports\ReportQuery;
use App\Modernization\Reports\ReportResult;
use App\Policies\Modernization\Reports\ReportPolicy;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminReportsWorkbench extends Component
{
    public string $reportKey = 'sales';

    public string $fromDate = '2026-05-01';

    public string $toDate = '2026-05-02';

    public string $storeId = '';

    public string $currency = '';

    public string $role = 'reports';

    public bool $showCsv = false;

    public function selectReport(string $reportKey): void
    {
        if (! array_key_exists($reportKey, $this->definitions())) {
            return;
        }

        $this->reportKey = $reportKey;
        $this->showCsv = false;
    }

    public function setRole(string $role): void
    {
        if (! in_array($role, ['reports', 'read-only', 'denied'], true)) {
            return;
        }

        $this->role = $role;
    }

    public function toggleCsv(): void
    {
        $this->showCsv = ! $this->showCsv;
    }

    public function render(ReportCatalog $catalog, ReportQuery $query, ReportPolicy $policy): View
    {
        $definition = $catalog->get($this->reportKey);
        $canViewReports = $policy->view($this->fixtureUser());
        $result = $canViewReports ? $query->run($definition, $this->filters()) : null;

        return view('livewire.admin-reports-workbench', [
            'definitions' => $this->definitions(),
            'selectedDefinition' => $definition,
            'canViewReports' => $canViewReports,
            'result' => $result,
            'displayCurrency' => $result instanceof ReportResult ? $this->displayCurrency($result) : 'Unavailable',
            'csvPreview' => $result instanceof ReportResult ? $query->exportCsv($result) : '',
        ]);
    }

    /**
     * @return array<string, ReportDefinition>
     */
    private function definitions(): array
    {
        return app(ReportCatalog::class)->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(): array
    {
        return array_filter([
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'store_id' => $this->storeId,
            'currency' => $this->currency,
        ], fn (mixed $value): bool => $value !== '' && $value !== null);
    }

    private function fixtureUser(): User
    {
        $user = new User;
        $user->forceFill([
            'id' => 902,
            'name' => "{$this->role} reports fixture",
            'email' => "{$this->role}-reports@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }

    private function displayCurrency(ReportResult $result): string
    {
        $currencies = collect($result->rows)
            ->pluck('currency')
            ->unique()
            ->values();

        if ($currencies->count() > 1) {
            return 'Mixed';
        }

        return $result->currency ?? 'None';
    }
}
