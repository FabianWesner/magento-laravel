<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Integrations\IntegrationDiagnosticsCatalog;
use App\Policies\Modernization\Integrations\IntegrationPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class IntegrationApiWorkbench extends Component
{
    public string $query = '';

    public string $status = '';

    public string $kind = '';

    public string $featureId = '';

    public string $section = 'contracts';

    public string $role = 'integrations';

    public function setSection(string $section): void
    {
        if (! in_array($section, ['contracts', 'adapters', 'callbacks', 'problems'], true)) {
            return;
        }

        $this->section = $section;
    }

    public function clearFilters(): void
    {
        $this->query = '';
        $this->status = '';
        $this->kind = '';
        $this->featureId = '';
        $this->section = 'contracts';
    }

    public function render(IntegrationDiagnosticsCatalog $catalog, IntegrationPolicy $policy): View
    {
        $this->status = $this->allowedStatus();
        $this->kind = $this->allowedKind();
        $this->featureId = $this->allowedFeatureId();
        $this->section = $this->allowedSection();

        $canViewIntegrations = $this->canViewIntegrations($policy);
        $rows = $canViewIntegrations ? $catalog->filtered($this->filters()) : [];
        $contractRows = $this->contractRows($rows);
        $adapterRows = $this->adapterRows($rows);
        $callbackRows = $this->callbackRows($rows);
        $problemRows = $this->problemRows($rows);

        return view('livewire.integration-api-workbench', [
            'canViewIntegrations' => $canViewIntegrations,
            'rows' => $rows,
            'contractRows' => $contractRows,
            'adapterRows' => $adapterRows,
            'callbackRows' => $callbackRows,
            'problemRows' => $problemRows,
            'currentRows' => $this->currentRows($contractRows, $adapterRows, $callbackRows, $problemRows),
            'summary' => $catalog->summary($rows),
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
            'kind' => $this->allowedKind(),
            'feature_id' => $this->allowedFeatureId(),
        ], fn (string $value): bool => $value !== '');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function contractRows(array $rows): array
    {
        return collect($rows)->where('kind', 'api_contract')->values()->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function adapterRows(array $rows): array
    {
        return collect($rows)->where('kind', 'integration_adapter')->values()->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function callbackRows(array $rows): array
    {
        return collect($rows)->where('has_callback', true)->values()->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function problemRows(array $rows): array
    {
        return collect($rows)->where('needs_attention', true)->values()->all();
    }

    /**
     * @param  list<array<string, mixed>>  $contractRows
     * @param  list<array<string, mixed>>  $adapterRows
     * @param  list<array<string, mixed>>  $callbackRows
     * @param  list<array<string, mixed>>  $problemRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $contractRows, array $adapterRows, array $callbackRows, array $problemRows): array
    {
        return match ($this->allowedSection()) {
            'adapters' => $adapterRows,
            'callbacks' => $callbackRows,
            'problems' => $problemRows,
            default => $contractRows,
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
            'kind' => $this->allowedKind(),
            'feature_id' => $this->allowedFeatureId(),
        ], fn (string $value): bool => $value !== '');
    }

    private function allowedStatus(): string
    {
        return in_array($this->status, ['', 'contract-test-required', 'mock-required', 'sandbox-configured', 'callback-required', 'oauth-required', 'attention'], true) ? $this->status : '';
    }

    private function allowedKind(): string
    {
        return in_array($this->kind, ['', 'api_contract', 'integration_adapter'], true) ? $this->kind : '';
    }

    private function allowedFeatureId(): string
    {
        return in_array($this->featureId, ['', 'AD-018', 'SF-015', 'SF-016', 'API-001', 'API-002', 'API-003', 'API-004', 'API-005', 'API-006', 'CJ-002', 'CJ-004', 'CJ-017'], true) ? $this->featureId : '';
    }

    private function allowedSection(): string
    {
        return in_array($this->section, ['contracts', 'adapters', 'callbacks', 'problems'], true) ? $this->section : 'contracts';
    }

    private function canViewIntegrations(IntegrationPolicy $policy): bool
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
            'id' => 940,
            'name' => "{$this->role} integration fixture",
            'email' => "{$this->role}-integration@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }
}
