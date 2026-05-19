<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Auth\AdminPermissionCatalog;
use App\Policies\Modernization\Auth\AdminPermissionPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class AdminPermissionsWorkbench extends Component
{
    public string $query = '';

    public string $status = '';

    public string $kind = '';

    public string $featureId = '';

    public string $subjectRole = '';

    public string $section = 'roles';

    public string $viewerRole = 'security';

    public function setSection(string $section): void
    {
        if (! in_array($section, ['roles', 'resources', 'api', 'problems'], true)) {
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
        $this->subjectRole = '';
        $this->section = 'roles';
    }

    public function render(AdminPermissionCatalog $catalog, AdminPermissionPolicy $policy): View
    {
        $this->status = $this->allowedStatus();
        $this->kind = $this->allowedKind();
        $this->featureId = $this->allowedFeatureId();
        $this->subjectRole = $this->allowedSubjectRole();
        $this->section = $this->allowedSection();
        $this->viewerRole = $this->allowedViewerRole();

        $canViewPermissions = $this->canViewPermissions($policy);
        $rows = $canViewPermissions ? $catalog->filtered($this->filters()) : [];
        $roleRows = $this->roleRows($rows);
        $resourceRows = $this->resourceRows($rows);
        $apiRows = $this->apiRows($rows);
        $problemRows = $this->problemRows($rows);

        return view('livewire.admin-permissions-workbench', [
            'canViewPermissions' => $canViewPermissions,
            'rows' => $rows,
            'roleRows' => $roleRows,
            'resourceRows' => $resourceRows,
            'apiRows' => $apiRows,
            'problemRows' => $problemRows,
            'currentRows' => $this->currentRows($roleRows, $resourceRows, $apiRows, $problemRows),
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
            'role' => $this->allowedSubjectRole(),
        ], fn (string $value): bool => $value !== '');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function roleRows(array $rows): array
    {
        return collect($rows)->where('kind', 'admin_role')->values()->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function resourceRows(array $rows): array
    {
        return collect($rows)->where('kind', 'acl_resource')->values()->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function apiRows(array $rows): array
    {
        return collect($rows)->where('kind', 'api_permission')->values()->all();
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
     * @param  list<array<string, mixed>>  $roleRows
     * @param  list<array<string, mixed>>  $resourceRows
     * @param  list<array<string, mixed>>  $apiRows
     * @param  list<array<string, mixed>>  $problemRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $roleRows, array $resourceRows, array $apiRows, array $problemRows): array
    {
        return match ($this->allowedSection()) {
            'resources' => $resourceRows,
            'api' => $apiRows,
            'problems' => $problemRows,
            default => $roleRows,
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
            'role' => $this->allowedSubjectRole(),
        ], fn (string $value): bool => $value !== '');
    }

    private function allowedStatus(): string
    {
        return in_array($this->status, ['', 'allowed', 'limited', 'read-only', 'denied', 'contract-test-required', 'attention'], true) ? $this->status : '';
    }

    private function allowedKind(): string
    {
        return in_array($this->kind, ['', 'admin_role', 'acl_resource', 'api_permission'], true) ? $this->kind : '';
    }

    private function allowedFeatureId(): string
    {
        return in_array($this->featureId, ['', 'AD-001', 'AD-012', 'API-001', 'API-002', 'API-003'], true) ? $this->featureId : '';
    }

    private function allowedSubjectRole(): string
    {
        return in_array($this->subjectRole, ['', 'full', 'partial', 'read-only', 'denied', 'admin', 'customer', 'guest'], true) ? $this->subjectRole : '';
    }

    private function allowedSection(): string
    {
        return in_array($this->section, ['roles', 'resources', 'api', 'problems'], true) ? $this->section : 'roles';
    }

    private function allowedViewerRole(): string
    {
        return in_array($this->viewerRole, ['security', 'full', 'read-only', 'denied'], true) ? $this->viewerRole : 'security';
    }

    private function canViewPermissions(AdminPermissionPolicy $policy): bool
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
            'id' => 950,
            'name' => "{$this->viewerRole} permissions fixture",
            'email' => "{$this->viewerRole}-permissions@example.test",
        ]);
        $user->setAttribute('role', $this->viewerRole);
        $user->exists = true;

        return $user;
    }
}
