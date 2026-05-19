<section aria-label="CMS SEO workbench" class="cms-card">
    <header class="cms-section-header">
        <p>Storefront CMS and SEO</p>
        <h2>{{ $pageFeature->label }}, {{ $blockFeature->label }}, {{ $widgetFeature->label }}, {{ $sitemapFeature->label }}, {{ $rewriteFeature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="cms-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Title, identifier, URL, redirect">
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
            Content state
            <select wire:model.live="contentState">
                <option value="">All states</option>
                <option value="active">Active</option>
                <option value="disabled">Disabled</option>
                <option value="no_route">No route / 404</option>
                <option value="redirect">Redirect</option>
            </select>
        </label>

        <label>
            Freshness
            <select wire:model.live="freshness">
                <option value="">All freshness</option>
                <option value="fresh">Fresh</option>
                <option value="stale">Stale</option>
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

    <div class="cms-toolbar">
        <div aria-label="CMS SEO summary" class="cms-summary">
            <span>{{ count($pageRows) }} page rows</span>
            <span>{{ count($blockRows) }} block rows</span>
            <span>{{ count($widgetRows) }} widget rows</span>
            <span>{{ count($seoRows) }} SEO rows</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="CMS SEO sections">
            <button type="button" wire:click="setSection('pages')" @class(['is-active' => $section === 'pages'])>Pages</button>
            <button type="button" wire:click="setSection('blocks')" @class(['is-active' => $section === 'blocks'])>Blocks</button>
            <button type="button" wire:click="setSection('widgets')" @class(['is-active' => $section === 'widgets'])>Widgets</button>
            <button type="button" wire:click="setSection('seo')" @class(['is-active' => $section === 'seo'])>SEO</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="cms-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="cms-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewContent)
        <div class="cms-state cms-state-danger" role="alert">
            Permission denied for CMS SEO role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="cms-state" role="status">
            There are no {{ $section }} facts matching this CMS SEO scope.
        </div>
    @elseif ($section === 'pages')
        <div class="cms-pages-layout">
            <article class="cms-page-primary">
                <p>{{ $selectedPage['store_view'] ?? 'all stores' }} / {{ $selectedPage['status_label'] ?? 'CMS page' }}</p>
                <h3>{{ $selectedPage['title'] ?? 'CMS page' }}</h3>
                <span>{{ $selectedPage['identifier'] ?? '' }} / {{ $selectedPage['canonical_url'] ?? '' }}</span>
                <em @class(['is-warning' => $selectedPage['is_stale'] ?? false])>{{ $selectedPage['freshness_label'] ?? 'Fresh' }}</em>
                <small>{{ $selectedPage['meta_description'] ?? '' }}</small>
            </article>

            <div class="cms-grid">
                @foreach ($pageRows as $row)
                    <article wire:key="cms-page-row-{{ $row['store_view'] }}-{{ $row['entity_id'] }}" @class(['cms-stale-card' => $row['is_stale'], 'cms-muted-card' => ! $row['is_active']])>
                        <span>{{ $row['type_label'] }} / {{ $row['status_label'] }}</span>
                        <h3>{{ $row['title'] }}</h3>
                        <p>{{ $row['identifier'] }} / {{ $row['canonical_url'] }}</p>
                        <small>{{ $row['summary'] }}</small>
                        @if ($row['has_redirect'])
                            <em>{{ $row['redirect_summary'] }}</em>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    @elseif ($section === 'blocks')
        <div class="cms-grid">
            @foreach ($blockRows as $row)
                <article wire:key="cms-block-row-{{ $row['store_view'] }}-{{ $row['entity_id'] }}" @class(['cms-muted-card' => ! $row['is_active']])>
                    <span>{{ $row['store_view'] }} / {{ $row['status_label'] }}</span>
                    <h3>{{ $row['title'] }}</h3>
                    <p>{{ $row['identifier'] }}</p>
                    <small>{{ $row['summary'] }}</small>
                    <em>{{ $row['detail'] }}</em>
                </article>
            @endforeach
        </div>
    @elseif ($section === 'widgets')
        <div class="cms-grid">
            @foreach ($widgetRows as $row)
                <article wire:key="cms-widget-row-{{ $row['store_view'] }}-{{ $row['entity_id'] }}" @class(['cms-muted-card' => ! $row['is_active']])>
                    <span>{{ $row['store_view'] }} / {{ $row['status_label'] }}</span>
                    <h3>{{ $row['title'] }}</h3>
                    <p>{{ $row['summary'] }}</p>
                    <small>{{ $row['detail'] }}</small>
                    <button type="button" disabled>Preview Widget</button>
                </article>
            @endforeach
        </div>
    @else
        <div class="cms-grid">
            @foreach ($seoRows as $row)
                <article wire:key="cms-seo-row-{{ $row['type'] }}-{{ $row['store_view'] }}-{{ $row['entity_id'] }}" @class(['cms-stale-card' => $row['is_stale']])>
                    <span>{{ $row['type_label'] }} / {{ $row['freshness_label'] }}</span>
                    <h3>{{ $row['title'] }}</h3>
                    <p>{{ $row['canonical_url'] }}</p>
                    <small>{{ $row['summary'] }} / {{ $row['detail'] }}</small>
                    @if ($row['rss_url'] !== '')
                        <em>{{ $row['rss_url'] }}</em>
                    @elseif ($row['has_redirect'])
                        <em>{{ $row['redirect_summary'] }}</em>
                    @else
                        <em>{{ $row['item_count'] }} items</em>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</section>
