<section aria-label="Admin CMS design workbench" class="admin-cms-design-card">
    <header class="admin-cms-design-section-header">
        <p>Admin CMS and design diagnostics</p>
        <h2>{{ $pageFeature->label }}, {{ $blockFeature->label }}, {{ $widgetFeature->label }}, {{ $rewriteFeature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="admin-cms-design-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Title, identifier, rewrite, cache tag">
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
                <option value="disabled">Disabled</option>
                <option value="no_route">No route / 404</option>
                <option value="redirect">Redirect</option>
                <option value="canonical">Canonical</option>
                <option value="scoped">Store scoped</option>
                <option value="inherited">Inherits default</option>
                <option value="clean">Clean cache</option>
                <option value="stale">Stale cache</option>
                <option value="problem">Problems</option>
            </select>
        </label>

        <label>
            Type
            <select wire:model.live="type">
                <option value="">All types</option>
                <option value="cms_page">CMS page</option>
                <option value="cms_block">CMS block</option>
                <option value="catalog_widget_new">Catalog widget</option>
                <option value="cms_widget_block">Static block widget</option>
                <option value="url_rewrite">URL rewrite</option>
                <option value="design_scope">Design scope</option>
                <option value="cache">Cache dependency</option>
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

    <div class="admin-cms-design-toolbar">
        <div aria-label="Admin CMS design summary" class="admin-cms-design-summary">
            <span>{{ count($pageRows) }} pages</span>
            <span>{{ count($blockRows) }} blocks</span>
            <span>{{ count($widgetRows) }} widgets</span>
            <span>{{ count($designRows) }} design/cache rows</span>
            <span>{{ count($rewriteRows) }} rewrites</span>
            <span>{{ count($problemRows) }} problems</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Admin CMS design sections">
            <button type="button" wire:click="setSection('pages')" @class(['is-active' => $section === 'pages'])>Pages</button>
            <button type="button" wire:click="setSection('blocks')" @class(['is-active' => $section === 'blocks'])>Blocks</button>
            <button type="button" wire:click="setSection('widgets')" @class(['is-active' => $section === 'widgets'])>Widgets</button>
            <button type="button" wire:click="setSection('design')" @class(['is-active' => $section === 'design'])>Design</button>
            <button type="button" wire:click="setSection('rewrites')" @class(['is-active' => $section === 'rewrites'])>Rewrites</button>
            <button type="button" wire:click="setSection('problems')" @class(['is-active' => $section === 'problems'])>Problems</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="admin-cms-design-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="admin-cms-design-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewCmsDesign)
        <div class="admin-cms-design-state admin-cms-design-state-danger" role="alert">
            Permission denied for admin CMS design role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="admin-cms-design-state" role="status">
            There are no {{ $section }} facts matching this admin CMS design scope.
        </div>
    @else
        <div class="admin-cms-design-grid">
            @foreach ($currentRows as $row)
                <article wire:key="admin-cms-design-row-{{ $section }}-{{ $row['store_view'] }}-{{ $row['domain'] }}-{{ $row['type'] }}-{{ $row['entity_id'] }}-{{ $loop->index }}" @class(['admin-cms-design-problem-card' => $row['is_problem']])>
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
