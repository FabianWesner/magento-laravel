<section aria-label="Storefront catalog workbench" class="catalog-card">
    <header class="catalog-section-header">
        <p>Storefront catalog</p>
        <h2>{{ $feature->label }}</h2>
        <span>{{ implode(', ', $feature->featureIds) }}</span>
    </header>

    <div class="catalog-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Name, SKU, description">
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
            Category
            <select wire:model.live="category">
                <option value="">All categories</option>
                @foreach ($categories as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
        </label>

        <label>
            Product type
            <select wire:model.live="type">
                <option value="">All types</option>
                @foreach ($types as $option)
                    <option value="{{ $option }}">{{ \Illuminate\Support\Str::headline($option) }}</option>
                @endforeach
            </select>
        </label>

        <label>
            Stock
            <select wire:model.live="stockState">
                <option value="">All stock states</option>
                <option value="in_stock">In stock</option>
                <option value="out_of_stock">Out of stock</option>
            </select>
        </label>

        <label>
            Swatch
            <select wire:model.live="swatch">
                <option value="">All swatches</option>
                @foreach ($swatches as $option)
                    <option value="{{ $option }}">{{ \Illuminate\Support\Str::headline($option) }}</option>
                @endforeach
            </select>
        </label>

        <label>
            Sort
            <select wire:model.live="sort">
                <option value="name">Name</option>
                <option value="price">Price</option>
                <option value="rating">Rating</option>
                <option value="stock">Stock</option>
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

    <div class="catalog-toolbar">
        <div aria-label="Catalog summary" class="catalog-summary">
            <span>{{ count($rows) }} visible</span>
            <span>{{ count($allRows) }} snapshot rows</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Catalog display mode">
            <button type="button" wire:click="setMode('grid')" @class(['is-active' => $mode === 'grid'])>Grid</button>
            <button type="button" wire:click="setMode('list')" @class(['is-active' => $mode === 'list'])>List</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="catalog-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span>{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewCatalog)
        <div class="catalog-state catalog-state-danger" role="alert">
            Permission denied for catalog role `{{ $role }}`.
        </div>
    @elseif ($rows === [])
        <div class="catalog-state" role="status">
            There are no products matching the selection.
        </div>
    @else
        <div @class(['catalog-grid' => $mode === 'grid', 'catalog-list' => $mode === 'list'])>
            @foreach ($rows as $row)
                @php($product = $row['payload'])
                <article class="catalog-product">
                    <div class="catalog-product-image" aria-hidden="true">{{ $product['swatch'] ?? 'CAT' }}</div>
                    <div class="catalog-product-body">
                        <p>{{ $product['type'] ?? 'product' }} / {{ $product['category'] ?? 'Catalog' }}</p>
                        <h3>{{ $product['name'] }}</h3>
                        <span>{{ $product['sku'] }}</span>
                        <strong>{{ \Illuminate\Support\Number::currency((float) ($product['price'] ?? 0), in: 'USD', locale: 'en_US') }}</strong>
                        <em>{{ (int) ($product['stock'] ?? 0) > 0 ? 'In stock' : 'Out of stock' }} / {{ $product['visibility'] ?? 'Catalog' }}</em>
                        <small>{{ $product['short_description'] ?? 'Magento catalog snapshot' }}</small>
                        <div class="catalog-product-actions">
                            <button type="button" disabled>Add to Cart</button>
                            <button type="button" disabled>Wishlist</button>
                            <button type="button" disabled>Compare</button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
