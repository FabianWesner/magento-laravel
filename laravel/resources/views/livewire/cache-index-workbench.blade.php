<section aria-label="Cache index workbench" class="cache-index-card">
    <header class="cache-index-section-header">
        <p>Admin operations</p>
        <h2>{{ $cacheFeature->label }}, {{ $indexFeature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="cache-index-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Cache type, indexer, tag, error">
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
            Status
            <select wire:model.live="status">
                <option value="">All statuses</option>
                <option value="ready">Ready</option>
                <option value="clean">Clean</option>
                <option value="valid">Valid</option>
                <option value="invalidated">Invalidated</option>
                <option value="stale">Stale</option>
                <option value="reindex_required">Reindex required</option>
                <option value="processing">Processing</option>
                <option value="error">Error</option>
                <option value="disabled">Disabled</option>
                <option value="locked">Locked</option>
            </select>
        </label>

        <label>
            Type
            <select wire:model.live="type">
                <option value="">All types</option>
                <option value="cache">Cache</option>
                <option value="index">Index</option>
                <option value="compiler">Compiler</option>
                <option value="cron">Cron</option>
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

    <div class="cache-index-toolbar">
        <div aria-label="Cache index summary" class="cache-index-summary">
            <span>{{ count($cacheRows) }} cache rows</span>
            <span>{{ count($indexRows) }} index rows</span>
            <span>{{ count($cronRows) }} cron rows</span>
            <span>{{ count($lockRows) }} lock rows</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Cache index sections">
            <button type="button" wire:click="setSection('cache')" @class(['is-active' => $section === 'cache'])>Cache</button>
            <button type="button" wire:click="setSection('index')" @class(['is-active' => $section === 'index'])>Index</button>
            <button type="button" wire:click="setSection('cron')" @class(['is-active' => $section === 'cron'])>Cron</button>
            <button type="button" wire:click="setSection('locks')" @class(['is-active' => $section === 'locks'])>Locks</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="cache-index-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="cache-index-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewOperations)
        <div class="cache-index-state cache-index-state-danger" role="alert">
            Permission denied for cache index role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="cache-index-state" role="status">
            There are no {{ $section }} facts matching this cache index scope.
        </div>
    @else
        <div class="cache-index-grid">
            @foreach ($currentRows as $row)
                <article wire:key="cache-index-row-{{ $section }}-{{ $row['store_view'] }}-{{ $row['entity_id'] }}" @class(['cache-index-problem-card' => $row['is_problem']])>
                    <span>{{ $row['kind_label'] }} / {{ $row['status_label'] }} / {{ $row['freshness_label'] }}</span>
                    <h3>{{ $row['title'] }}</h3>
                    <p>{{ $row['summary'] }}</p>
                    @if ($row['detail'] !== '')
                        <small>{{ $row['detail'] }}</small>
                    @endif
                    @if ($row['tags'] !== '')
                        <strong>{{ $row['tags'] }}</strong>
                    @endif
                    @if ($row['affected'] !== '')
                        <small>{{ $row['affected'] }}</small>
                    @endif
                    @if ($row['cron_job'] !== '' || $row['schedule'] !== '')
                        <em>{{ $row['cron_job'] }}{{ $row['schedule'] !== '' ? ' / '.$row['schedule'] : '' }}</em>
                    @endif
                    @if ($row['lock_owner'] !== '')
                        <em>{{ $row['lock_owner'] }}</em>
                    @endif
                    @if ($row['failure_reason'] !== '')
                        <em>{{ $row['failure_reason'] }}</em>
                    @endif
                    <button type="button" disabled>{{ $section === 'locks' ? 'Inspect Lock' : 'Preview Operation' }}</button>
                </article>
            @endforeach
        </div>
    @endif
</section>
