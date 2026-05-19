<section aria-label="Integrations API workbench" class="integration-api-card">
    <header class="integration-api-section-header">
        <p>Admin integrations and APIs</p>
        <h2>AD-018, API-001 through API-006</h2>
        <span>Legacy contracts, sandbox endpoints, callbacks, OAuth, retry, timeout, and rollback diagnostics.</span>
    </header>

    <div class="integration-api-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="API, adapter, endpoint, role, provider">
        </label>

        <label>
            Status
            <select wire:model.live="status">
                <option value="">All statuses</option>
                <option value="contract-test-required">Contract test required</option>
                <option value="mock-required">Mock required</option>
                <option value="sandbox-configured">Sandbox configured</option>
                <option value="callback-required">Callback required</option>
                <option value="oauth-required">OAuth required</option>
                <option value="attention">Needs attention</option>
            </select>
        </label>

        <label>
            Kind
            <select wire:model.live="kind">
                <option value="">All kinds</option>
                <option value="api_contract">API contract</option>
                <option value="integration_adapter">Integration adapter</option>
            </select>
        </label>

        <label>
            Feature
            <select wire:model.live="featureId">
                <option value="">All features</option>
                <option value="AD-018">AD-018</option>
                <option value="SF-015">SF-015</option>
                <option value="SF-016">SF-016</option>
                <option value="API-001">API-001</option>
                <option value="API-002">API-002</option>
                <option value="API-003">API-003</option>
                <option value="API-004">API-004</option>
                <option value="API-005">API-005</option>
                <option value="API-006">API-006</option>
                <option value="CJ-002">CJ-002</option>
                <option value="CJ-004">CJ-004</option>
                <option value="CJ-017">CJ-017</option>
            </select>
        </label>

        <label>
            Role
            <select wire:model.live="role">
                <option value="integrations">Integrations</option>
                <option value="read-only">Read only</option>
                <option value="denied">Denied</option>
            </select>
        </label>
    </div>

    <div class="integration-api-toolbar">
        <div aria-label="Integrations API summary" class="integration-api-summary">
            <span>{{ $summary['api_contracts'] }} API contracts</span>
            <span>{{ $summary['adapters'] }} adapters</span>
            <span>{{ $summary['sandbox_adapters'] }} sandbox adapters</span>
            <span>{{ $summary['callbacks'] }} callbacks/OAuth</span>
            <span>{{ $summary['attention'] }} attention rows</span>
        </div>
        <div role="toolbar" aria-label="Integrations API sections">
            <button type="button" wire:click="setSection('contracts')" @class(['is-active' => $section === 'contracts'])>Contracts</button>
            <button type="button" wire:click="setSection('adapters')" @class(['is-active' => $section === 'adapters'])>Adapters</button>
            <button type="button" wire:click="setSection('callbacks')" @class(['is-active' => $section === 'callbacks'])>Callbacks</button>
            <button type="button" wire:click="setSection('problems')" @class(['is-active' => $section === 'problems'])>Problems</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="integration-api-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="integration-api-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewIntegrations)
        <div class="integration-api-state integration-api-state-danger" role="alert">
            Permission denied for integration role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="integration-api-state" role="status">
            There are no {{ $section }} rows matching this integrations API scope.
        </div>
    @else
        <div class="integration-api-grid">
            @foreach ($currentRows as $row)
                <article wire:key="integration-api-{{ $section }}-{{ $row['kind'] }}-{{ $row['key'] }}" @class(['integration-api-problem-card' => $row['needs_attention']])>
                    <span>{{ $row['kind_label'] }} / {{ implode(', ', $row['feature_ids']) }} / {{ $row['status_label'] }}</span>
                    <h3>{{ $row['title'] }}</h3>
                    <p>{{ $row['summary'] }}</p>
                    <strong>{{ $row['method'] }} {{ $row['endpoint'] }}</strong>
                    <small>Context: {{ $row['context'] }}</small>
                    <small>Config: {{ $row['config_path'] }} / Secret: {{ $row['secret_key'] }}</small>
                    <small>Providers or roles: {{ $row['providers'] === [] ? 'none' : implode(', ', $row['providers']) }}</small>
                    <em>Retry: {{ $row['retry'] }} / Timeout: {{ $row['timeout'] }} / Rollback: {{ $row['rollback'] }}</em>
                    <button type="button" disabled>Inspect Only</button>
                </article>
            @endforeach
        </div>
    @endif
</section>
