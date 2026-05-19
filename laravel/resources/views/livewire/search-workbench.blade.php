<section aria-label="Search workbench" class="search-card">
    <header class="search-section-header">
        <p>Storefront search</p>
        <h2>{{ $feature->label }}</h2>
        <span>{{ implode(', ', $feature->featureIds) }}</span>
    </header>

    <div class="search-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Query, synonym, SKU, URL">
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
            Query type
            <select wire:model.live="queryType">
                <option value="">All query types</option>
                <option value="quick">Quick</option>
                <option value="advanced">Advanced</option>
            </select>
        </label>

        <label>
            Results
            <select wire:model.live="resultState">
                <option value="">All result states</option>
                <option value="has_results">Has results</option>
                <option value="no_results">No results</option>
            </select>
        </label>

        <label>
            Redirect
            <select wire:model.live="redirectState">
                <option value="">All redirects</option>
                <option value="redirected">Redirected</option>
                <option value="not_redirected">Not redirected</option>
            </select>
        </label>

        <label>
            Index
            <select wire:model.live="indexState">
                <option value="">All index states</option>
                <option value="fresh">Fresh</option>
                <option value="stale">Stale</option>
            </select>
        </label>

        <label>
            Sort
            <select wire:model.live="sort">
                <option value="snapshot">Snapshot order</option>
                <option value="query">Query</option>
                <option value="results_count">Result count</option>
                <option value="query_type">Query type</option>
            </select>
        </label>

        <label>
            Direction
            <select wire:model.live="sortDirection">
                <option value="asc">Ascending</option>
                <option value="desc">Descending</option>
            </select>
        </label>

        <label>
            Limit
            <select wire:model.live="limit">
                <option value="all">All rows</option>
                <option value="1">1 row</option>
                <option value="2">2 rows</option>
                <option value="3">3 rows</option>
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

    <div class="search-toolbar">
        <div aria-label="Search summary" class="search-summary">
            <span>{{ count($rows) }} search rows</span>
            <span>{{ count($allRows) }} snapshot rows</span>
            <span>{{ count($resultProducts) }} product rows</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Search sections">
            <button type="button" wire:click="setSection('results')" @class(['is-active' => $section === 'results'])>Results</button>
            <button type="button" wire:click="setSection('terms')" @class(['is-active' => $section === 'terms'])>Terms</button>
            <button type="button" wire:click="setSection('seo')" @class(['is-active' => $section === 'seo'])>SEO/RSS</button>
            <button type="button" wire:click="setSection('index')" @class(['is-active' => $section === 'index'])>Index</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="search-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="search-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewSearch)
        <div class="search-state search-state-danger" role="alert">
            Permission denied for search role `{{ $role }}`.
        </div>
    @elseif ($rows === [])
        <div class="search-state" role="status">
            There are no search facts matching the selection.
        </div>
    @elseif ($section === 'results')
        <div class="search-results-layout">
            <article class="search-result-primary">
                <p>{{ $selectedSearch['query_type_label'] }} search</p>
                <h3>{{ $selectedSearch['query'] }}</h3>
                <span>{{ $selectedSearch['results_count'] }} results / {{ $selectedSearch['result_sku_summary'] }}</span>
                <em @class(['is-warning' => $selectedSearch['is_index_stale']])>{{ $selectedSearch['index_label'] }}</em>
                @if ($selectedSearch['has_redirect'])
                    <small>{{ $selectedSearch['redirect_summary'] }}</small>
                @else
                    <small>{{ $selectedSearch['canonical_url'] }}</small>
                @endif
            </article>

            <div class="search-product-grid">
                @forelse ($resultProducts as $product)
                    <article class="search-product" wire:key="search-product-{{ $selectedSearch['store_view'] }}-{{ $selectedSearch['entity_id'] }}-{{ $product['store_view'] }}-{{ $product['entity_id'] }}">
                        <div aria-hidden="true">{{ $product['type'] }}</div>
                        <p>{{ $product['type_label'] }} / {{ $product['visibility'] }}</p>
                        <h3>{{ $product['name'] }}</h3>
                        <span>{{ $product['sku'] }} / {{ $product['url'] }}</span>
                        <strong>{{ $product['price'] }}</strong>
                        <em>{{ $product['stock_label'] }} / {{ $product['stock_qty'] }} available / {{ $product['rating'] }} rating</em>
                        <small>{{ $product['short_description'] }}</small>
                    </article>
                @empty
                    <div class="search-state" role="status">
                        No product rows are attached to this search snapshot.
                    </div>
                @endforelse
            </div>

            <div class="search-row-list" aria-label="Search rows">
                @foreach ($rows as $row)
                    <article wire:key="search-result-row-{{ $row['store_view'] }}-{{ $row['entity_id'] }}">
                        <h3>{{ $row['query'] }}</h3>
                        <span>{{ $row['query_type_label'] }} / {{ $row['results_count'] }} results / {{ $row['store_view'] }}</span>
                        <small>{{ $row['result_sku_summary'] }}</small>
                    </article>
                @endforeach
            </div>
        </div>
    @elseif ($section === 'terms')
        <div class="search-detail-grid">
            @foreach ($rows as $row)
                <article wire:key="search-term-row-{{ $row['store_view'] }}-{{ $row['entity_id'] }}">
                    <span>{{ $row['query_type_label'] }}</span>
                    <h3>{{ $row['query'] }}</h3>
                    <p>{{ $row['synonym_summary'] }}</p>
                    <small>{{ $row['filter_summary'] }}</small>
                </article>
            @endforeach
        </div>
    @elseif ($section === 'seo')
        <div class="search-detail-grid">
            @foreach ($rows as $row)
                <article wire:key="search-seo-row-{{ $row['store_view'] }}-{{ $row['entity_id'] }}">
                    <span>{{ $row['query'] }}</span>
                    <h3>{{ $row['canonical_url'] }}</h3>
                    <p>{{ $row['rss_url'] }}</p>
                    <small>{{ $row['redirect_summary'] }}</small>
                    <button type="button" disabled>Open RSS</button>
                </article>
            @endforeach
        </div>
    @else
        <div class="search-detail-grid">
            @foreach ($rows as $row)
                <article wire:key="search-index-row-{{ $row['store_view'] }}-{{ $row['entity_id'] }}" @class(['search-stale-card' => $row['is_index_stale']])>
                    <span>{{ $row['store_view'] }} / entity {{ $row['entity_id'] }}</span>
                    <h3>{{ $row['index_label'] }}</h3>
                    <p>{{ $row['query'] }}</p>
                    <small>{{ $row['is_index_stale'] ? 'Requires catalog search reindex parity check.' : 'Search index snapshot is current.' }}</small>
                </article>
            @endforeach
        </div>
    @endif
</section>
