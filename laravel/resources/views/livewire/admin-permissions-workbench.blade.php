<section aria-label="Admin permissions workbench" class="admin-permissions-card">
    <header class="admin-permissions-section-header">
        <p>Admin users, roles, and API permissions</p>
        <h2>AD-001, AD-012, API-001 through API-003</h2>
        <span>Fixture-backed ACL diagnostics for admin access, API users, REST roles, OAuth consumers, denied states, and rollback boundaries.</span>
    </header>

    <div class="admin-permissions-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Role, resource, API, OAuth, guard">
        </label>

        <label>
            Status
            <select wire:model.live="status">
                <option value="">All statuses</option>
                <option value="allowed">Allowed</option>
                <option value="limited">Limited</option>
                <option value="read-only">Read only</option>
                <option value="denied">Denied</option>
                <option value="contract-test-required">Contract test required</option>
                <option value="attention">Needs attention</option>
            </select>
        </label>

        <label>
            Kind
            <select wire:model.live="kind">
                <option value="">All kinds</option>
                <option value="admin_role">Admin role</option>
                <option value="acl_resource">ACL resource</option>
                <option value="api_permission">API permission</option>
            </select>
        </label>

        <label>
            Feature
            <select wire:model.live="featureId">
                <option value="">All features</option>
                <option value="AD-001">AD-001</option>
                <option value="AD-012">AD-012</option>
                <option value="API-001">API-001</option>
                <option value="API-002">API-002</option>
                <option value="API-003">API-003</option>
            </select>
        </label>

        <label>
            Role
            <select wire:model.live="subjectRole">
                <option value="">All roles</option>
                <option value="full">Full</option>
                <option value="partial">Partial</option>
                <option value="read-only">Read only</option>
                <option value="denied">Denied</option>
                <option value="admin">API admin</option>
                <option value="customer">API customer</option>
                <option value="guest">API guest</option>
            </select>
        </label>

        <label>
            Viewer
            <select wire:model.live="viewerRole">
                <option value="security">Security</option>
                <option value="full">Full</option>
                <option value="read-only">Read only</option>
                <option value="denied">Denied</option>
            </select>
        </label>
    </div>

    <div class="admin-permissions-toolbar">
        <div aria-label="Admin permissions summary" class="admin-permissions-summary">
            <span>{{ $summary['admin_roles'] }} admin roles</span>
            <span>{{ $summary['acl_resources'] }} ACL resources</span>
            <span>{{ $summary['api_permissions'] }} API permissions</span>
            <span>{{ $summary['allowed_rows'] }} allowed rows</span>
            <span>{{ $summary['attention'] }} attention rows</span>
        </div>
        <div role="toolbar" aria-label="Admin permissions sections">
            <button type="button" wire:click="setSection('roles')" @class(['is-active' => $section === 'roles'])>Roles</button>
            <button type="button" wire:click="setSection('resources')" @class(['is-active' => $section === 'resources'])>Resources</button>
            <button type="button" wire:click="setSection('api')" @class(['is-active' => $section === 'api'])>API</button>
            <button type="button" wire:click="setSection('problems')" @class(['is-active' => $section === 'problems'])>Problems</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="admin-permissions-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="admin-permissions-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewPermissions)
        <div class="admin-permissions-state admin-permissions-state-danger" role="alert">
            Permission denied for admin permissions viewer role `{{ $viewerRole }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="admin-permissions-state" role="status">
            There are no {{ $section }} rows matching this admin permissions scope.
        </div>
    @else
        <div class="admin-permissions-grid">
            @foreach ($currentRows as $row)
                <article wire:key="admin-permissions-{{ $section }}-{{ $row['kind'] }}-{{ $row['key'] }}" @class(['admin-permissions-problem-card' => $row['needs_attention']])>
                    <span>{{ $row['kind_label'] }} / {{ implode(', ', $row['feature_ids']) }} / {{ $row['status_label'] }}</span>
                    <h3>{{ $row['title'] }}</h3>
                    <p>{{ $row['summary'] }}</p>
                    <strong>{{ $row['guard'] }} / {{ $row['route'] }}</strong>
                    <small>Roles: {{ $row['roles'] === [] ? 'none' : implode(', ', $row['roles']) }}</small>
                    <small>Permissions: {{ $row['permissions'] === [] ? 'none' : implode(', ', $row['permissions']) }}</small>
                    <small>Legacy resource: {{ $row['legacy_resource'] }}</small>
                    <em>Risk: {{ $row['risk'] }} / Rollback: {{ $row['rollback'] }}</em>
                    <button type="button" disabled>Inspect Only</button>
                </article>
            @endforeach
        </div>
    @endif
</section>
