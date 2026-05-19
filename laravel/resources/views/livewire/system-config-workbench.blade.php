<section aria-label="System config workbench" class="system-config-card">
    <header class="system-config-section-header">
        <p>Admin configuration</p>
        <h2>{{ $configFeature->label }}, {{ $scopeFeature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="system-config-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Config path, scope, value, error">
        </label>

        <label>
            Store scope
            <select wire:model.live="storeId">
                <option value="">All stores</option>
                <option value="9001">Store 9001</option>
                <option value="9002">Store 9002</option>
            </select>
        </label>

        <label>
            Store view
            <select wire:model.live="storeView">
                <option value="">All store views</option>
                <option value="default">Default</option>
                <option value="de">DE</option>
            </select>
        </label>

        <label>
            State
            <select wire:model.live="state">
                <option value="">All states</option>
                <option value="valid">Valid</option>
                <option value="invalid">Invalid</option>
                <option value="inherited">Inherited</option>
                <option value="secret">Secret</option>
                <option value="env_override">Env override</option>
                <option value="cache">Cache changed</option>
            </select>
        </label>

        <label>
            Group
            <select wire:model.live="group">
                <option value="">All groups</option>
                <option value="web">Web</option>
                <option value="catalog">Catalog</option>
                <option value="payment">Payment</option>
                <option value="general">General</option>
                <option value="currency">Currency</option>
                <option value="scope">Store scope</option>
            </select>
        </label>

        <label>
            Role
            <select wire:model.live="role">
                <option value="catalog">Catalog</option>
                <option value="read-only">Read only</option>
                <option value="denied">Denied</option>
            </select>
        </label>
    </div>

    <div class="system-config-toolbar">
        <div aria-label="System config summary" class="system-config-summary">
            <span>{{ count($scopeRows) }} scope rows</span>
            <span>{{ count($configRows) }} config rows</span>
            <span>{{ count($validationRows) }} validation rows</span>
            <span>{{ count($secretRows) }} safety rows</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="System config sections">
            <button type="button" wire:click="setSection('config')" @class(['is-active' => $section === 'config'])>Config</button>
            <button type="button" wire:click="setSection('scopes')" @class(['is-active' => $section === 'scopes'])>Scopes</button>
            <button type="button" wire:click="setSection('validation')" @class(['is-active' => $section === 'validation'])>Validation</button>
            <button type="button" wire:click="setSection('secrets')" @class(['is-active' => $section === 'secrets'])>Secrets and cache</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="system-config-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="system-config-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewConfig)
        <div class="system-config-state system-config-state-danger" role="alert">
            Permission denied for system config role `{{ $role }}`.
        </div>
    @else
        <div class="system-config-plan" aria-label="System config service plan">
            <span>Implementation: {{ $configImplementation }}</span>
            <span>Source model plan: {{ $sourceModelPlan }}</span>
            <span>Secret path configured: {{ $secretPathConfigured ? 'yes' : 'no' }}</span>
        </div>

        @if ($currentRows === [])
            <div class="system-config-state" role="status">
                There are no {{ $section }} facts matching this system configuration scope.
            </div>
        @else
            <div class="system-config-grid">
                @foreach ($currentRows as $row)
                    <article wire:key="system-config-row-{{ $section }}-{{ $row['store_view'] }}-{{ $row['entity_id'] }}" @class(['system-config-problem-card' => $row['is_problem'], 'system-config-sensitive-card' => $row['is_sensitive']])>
                        <span>{{ $row['domain_label'] }} / {{ $row['state_label'] }} / {{ $row['cache_label'] }}</span>
                        <h3>{{ $row['title'] }}</h3>
                        <p>{{ $row['summary'] }}</p>
                        @if ($row['path'] !== '')
                            <small>{{ $row['path'] }}</small>
                        @endif
                        @if ($row['value'] !== '')
                            <strong>{{ $row['value'] }}</strong>
                        @endif
                        @if ($row['attempted_value'] !== '')
                            <em>Attempted: {{ $row['attempted_value'] }}</em>
                        @endif
                        @if ($row['source_options'] !== '')
                            <em>Options: {{ $row['source_options'] }}</em>
                        @endif
                        @if ($row['fallback_chain'] !== '')
                            <em>Fallback: {{ $row['fallback_chain'] }}</em>
                        @endif
                        @if ($row['scope_label'] !== '')
                            <small>{{ $row['scope_label'] }}</small>
                        @endif
                        @if ($row['detail'] !== '')
                            <small>{{ $row['detail'] }}</small>
                        @endif
                        @if ($row['error'] !== '')
                            <em>{{ $row['error'] }}</em>
                        @endif
                        <button type="button" disabled>{{ $section === 'scopes' ? 'Inspect Scope' : 'Preview Config' }}</button>
                    </article>
                @endforeach
            </div>
        @endif
    @endif
</section>
