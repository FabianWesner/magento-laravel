<section aria-label="Admin store operations workbench" class="admin-store-operations-card">
    <header class="admin-store-operations-section-header">
        <p>Admin store operations diagnostics</p>
        <h2>{{ $storeFeature->label }}, {{ $backupFeature->label }}, {{ $systemFeature->label }}, {{ $templateFeature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="admin-store-operations-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Store, backup, template, URL">
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
                <option value="active">Active</option>
                <option value="inherited">Inherited</option>
                <option value="completed">Completed</option>
                <option value="scheduled">Scheduled</option>
                <option value="failed">Failed</option>
                <option value="healthy">Healthy</option>
                <option value="warning">Warning</option>
                <option value="customized">Customized</option>
                <option value="default">Default template</option>
                <option value="invalid">Invalid</option>
                <option value="canonical">Canonical</option>
                <option value="redirect">Redirect</option>
                <option value="fresh">Fresh</option>
                <option value="stale">Stale</option>
                <option value="problem">Problems</option>
            </select>
        </label>

        <label>
            Type
            <select wire:model.live="type">
                <option value="">All types</option>
                <option value="store_view">Store view</option>
                <option value="backup">Backup</option>
                <option value="system_info">System info</option>
                <option value="email_template">Email template</option>
                <option value="url_rewrite">URL rewrite</option>
                <option value="sitemap">Sitemap</option>
                <option value="rss">RSS feed</option>
            </select>
        </label>

        <label>
            Role
            <select wire:model.live="role">
                <option value="catalog">Catalog</option>
                <option value="read-only">Read only</option>
                <option value="full">Full</option>
                <option value="denied">Denied</option>
            </select>
        </label>
    </div>

    <div class="admin-store-operations-toolbar">
        <div aria-label="Admin store operations summary" class="admin-store-operations-summary">
            <span>{{ count($storeRows) }} stores</span>
            <span>{{ count($backupRows) }} backups</span>
            <span>{{ count($systemRows) }} system rows</span>
            <span>{{ count($templateRows) }} templates</span>
            <span>{{ count($rewriteRows) }} rewrites</span>
            <span>{{ count($sitemapRows) }} sitemap rows</span>
            <span>{{ count($problemRows) }} problems</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Admin store operations sections">
            <button type="button" wire:click="setSection('stores')" @class(['is-active' => $section === 'stores'])>Stores</button>
            <button type="button" wire:click="setSection('backups')" @class(['is-active' => $section === 'backups'])>Backups</button>
            <button type="button" wire:click="setSection('system')" @class(['is-active' => $section === 'system'])>System</button>
            <button type="button" wire:click="setSection('templates')" @class(['is-active' => $section === 'templates'])>Templates</button>
            <button type="button" wire:click="setSection('rewrites')" @class(['is-active' => $section === 'rewrites'])>Rewrites</button>
            <button type="button" wire:click="setSection('sitemaps')" @class(['is-active' => $section === 'sitemaps'])>Sitemaps</button>
            <button type="button" wire:click="setSection('problems')" @class(['is-active' => $section === 'problems'])>Problems</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="admin-store-operations-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="admin-store-operations-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewStoreOperations)
        <div class="admin-store-operations-state admin-store-operations-state-danger" role="alert">
            Permission denied for admin store operations role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="admin-store-operations-state" role="status">
            There are no {{ $section }} facts matching this admin store operations scope.
        </div>
    @else
        <div class="admin-store-operations-grid">
            @foreach ($currentRows as $row)
                <article wire:key="admin-store-operations-row-{{ $section }}-{{ $row['store_view'] }}-{{ $row['type'] }}-{{ $row['entity_id'] }}-{{ $loop->index }}" @class(['admin-store-operations-problem-card' => $row['is_problem']])>
                    <span>{{ $row['type_label'] }} / {{ $row['status_label'] }}</span>
                    <h3>{{ $row['title'] }}</h3>
                    @if ($row['subtitle'] !== '')
                        <strong>{{ $row['subtitle'] }}</strong>
                    @endif
                    <p>{{ $row['summary'] }}</p>
                    @if ($row['detail'] !== '')
                        <small>{{ $row['detail'] }}</small>
                    @endif
                    @if ($row['metric'] !== '')
                        <em>{{ $row['metric'] }}</em>
                    @endif
                    <small>Store {{ $row['store_id'] }} / {{ $row['store_view'] }}</small>
                    <button type="button" disabled>{{ $row['action_label'] }}</button>
                </article>
            @endforeach
        </div>
    @endif
</section>
