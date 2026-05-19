<section aria-label="Admin catalog management workbench" class="admin-catalog-card">
    <header class="admin-catalog-section-header">
        <p>Admin catalog diagnostics</p>
        <h2>{{ $productFeature->label }}, {{ $categoryFeature->label }}, {{ $mediaFeature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="admin-catalog-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="SKU, category, attribute, media">
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
                <option value="enabled">Enabled</option>
                <option value="disabled">Disabled</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="in_stock">In stock</option>
                <option value="out_of_stock">Out of stock</option>
                <option value="ready">Ready</option>
                <option value="tracked">Tracked</option>
                <option value="missing_media">Missing media</option>
                <option value="requires_login">Requires login</option>
                <option value="shareable">Shareable</option>
                <option value="problem">Problems</option>
            </select>
        </label>

        <label>
            Type
            <select wire:model.live="type">
                <option value="">All types</option>
                <option value="simple">Simple</option>
                <option value="configurable">Configurable</option>
                <option value="grouped">Grouped</option>
                <option value="bundle">Bundle</option>
                <option value="virtual">Virtual</option>
                <option value="category">Category</option>
                <option value="media">Media</option>
                <option value="downloadable">Downloadable</option>
                <option value="attribute_set">Attribute set</option>
                <option value="custom_option">Custom option</option>
                <option value="configurable_attribute">Configurable attribute</option>
                <option value="category_attribute">Category attribute</option>
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

    <div class="admin-catalog-toolbar">
        <div aria-label="Admin catalog summary" class="admin-catalog-summary">
            <span>{{ count($productRows) }} products</span>
            <span>{{ count($categoryRows) }} categories</span>
            <span>{{ count($attributeRows) }} attribute rows</span>
            <span>{{ count($mediaRows) }} media rows</span>
            <span>{{ count($downloadRows) }} downloads</span>
            <span>{{ count($problemRows) }} problems</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Admin catalog sections">
            <button type="button" wire:click="setSection('products')" @class(['is-active' => $section === 'products'])>Products</button>
            <button type="button" wire:click="setSection('categories')" @class(['is-active' => $section === 'categories'])>Categories</button>
            <button type="button" wire:click="setSection('attributes')" @class(['is-active' => $section === 'attributes'])>Attributes</button>
            <button type="button" wire:click="setSection('media')" @class(['is-active' => $section === 'media'])>Media</button>
            <button type="button" wire:click="setSection('downloads')" @class(['is-active' => $section === 'downloads'])>Downloads</button>
            <button type="button" wire:click="setSection('problems')" @class(['is-active' => $section === 'problems'])>Problems</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="admin-catalog-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="admin-catalog-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewCatalog)
        <div class="admin-catalog-state admin-catalog-state-danger" role="alert">
            Permission denied for admin catalog role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="admin-catalog-state" role="status">
            There are no {{ $section }} facts matching this admin catalog scope.
        </div>
    @else
        <div class="admin-catalog-grid">
            @foreach ($currentRows as $row)
                <article wire:key="admin-catalog-row-{{ $section }}-{{ $row['store_view'] }}-{{ $row['domain'] }}-{{ $row['type'] }}-{{ $row['entity_id'] }}-{{ $loop->index }}" @class(['admin-catalog-problem-card' => $row['is_problem']])>
                    <span>{{ $row['type_label'] }} / {{ $row['status_label'] }}{{ $row['stock_status'] !== '' ? ' / '.\Illuminate\Support\Str::headline($row['stock_status']) : '' }}</span>
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
