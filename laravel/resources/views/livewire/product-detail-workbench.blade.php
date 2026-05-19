<section aria-label="Product detail workbench" class="product-card">
    <header class="product-section-header">
        <p>Product detail</p>
        <h2>{{ $feature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="product-controls">
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
            Product
            <select wire:model.live="productId">
                <option value="">First matching product</option>
                @foreach ($productOptions as $product)
                    @php($productPayload = $product['payload'])
                    <option value="{{ $product['entity_id'] }}">{{ $productPayload['name'] ?? $productPayload['sku'] ?? $product['entity_id'] }}</option>
                @endforeach
            </select>
        </label>

        <label>
            Product type
            <select wire:model.live="productType">
                <option value="">All types</option>
                <option value="simple">Simple</option>
                <option value="configurable">Configurable</option>
                <option value="downloadable">Downloadable</option>
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

    <div class="product-toolbar">
        <div aria-label="Product summary" class="product-summary">
            <span>{{ count($products) }} product rows</span>
            <span>{{ count($mediaRows) }} media rows</span>
            <span>{{ count($downloadRows) }} download rows</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Product detail sections">
            <button type="button" wire:click="setSection('overview')" @class(['is-active' => $section === 'overview'])>Overview</button>
            <button type="button" wire:click="setSection('media')" @class(['is-active' => $section === 'media'])>Media</button>
            <button type="button" wire:click="setSection('options')" @class(['is-active' => $section === 'options'])>Options</button>
            <button type="button" wire:click="setSection('commerce')" @class(['is-active' => $section === 'commerce'])>Commerce</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="product-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span>{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewProduct)
        <div class="product-state product-state-danger" role="alert">
            Permission denied for product role `{{ $role }}`.
        </div>
    @elseif ($selectedProduct === [])
        <div class="product-state" role="status">
            There are no product detail facts for this scope.
        </div>
    @elseif ($section === 'overview')
        <div class="product-overview">
            <div class="product-hero" aria-hidden="true">
                {{ $selectedProduct['swatch'] ?? $selectedProduct['type'] ?? 'PDP' }}
            </div>
            <article>
                <p>{{ \Illuminate\Support\Str::headline($selectedProduct['type'] ?? 'product') }}</p>
                <h3>{{ $selectedProduct['name'] ?? 'Product' }}</h3>
                <span>{{ $selectedProduct['sku'] ?? 'sku-unavailable' }}</span>
                <strong>{{ \Illuminate\Support\Number::currency((float) ($selectedProduct['final_price'] ?? $selectedProduct['price'] ?? 0), in: $selectedProduct['currency'] ?? 'USD', locale: 'en_US') }}</strong>
                <em>{{ (bool) ($selectedProduct['stock']['is_in_stock'] ?? false) ? 'In stock' : 'Out of stock' }} / {{ $selectedProduct['visibility'] ?? 'Catalog, Search' }}</em>
                <span>{{ $selectedProduct['stock']['qty'] ?? 0 }} available / {{ $selectedProduct['rating']['reviews_count'] ?? 0 }} reviews / {{ $selectedProduct['rating']['summary'] ?? 'n/a' }} rating</span>
                <small>{{ $selectedProduct['short_description'] ?? 'Magento product detail snapshot' }}</small>
                <div class="product-actions">
                    <button type="button" disabled>Add to Cart</button>
                    <button type="button" disabled>Add to Wishlist</button>
                    <button type="button" disabled>Compare</button>
                    <button type="button" disabled>Send to Friend</button>
                </div>
            </article>
        </div>
    @elseif ($section === 'media')
        <div class="media-grid">
            @forelse ($mediaRows as $media)
                @php($payload = $media['payload'])
                <article>
                    <div aria-hidden="true">image</div>
                    <h3>{{ $payload['gallery'][0]['label'] ?? 'Product media' }}</h3>
                    <p>{{ $payload['base_image'] ?? 'media path unavailable' }}</p>
                    <span>{{ (bool) ($payload['missing_media'] ?? false) ? 'Missing media' : 'Available media' }}</span>
                    @foreach (($payload['gallery'] ?? []) as $galleryItem)
                        <small>{{ $galleryItem['label'] }} / {{ $galleryItem['file'] }}</small>
                    @endforeach
                </article>
            @empty
                <div class="product-state" role="status">
                    No media gallery rows are attached to this product snapshot.
                </div>
            @endforelse
        </div>
    @elseif ($section === 'options')
        <div class="option-grid">
            @foreach (($selectedProduct['custom_options'] ?? []) as $option)
                <article>
                    <h3>{{ $option['title'] ?? 'Option' }}</h3>
                    <p>{{ $option['type'] ?? 'selection' }}</p>
                    <span>{{ (bool) ($option['is_required'] ?? false) ? 'Required' : 'Optional' }}</span>
                </article>
            @endforeach
            @foreach (($selectedProduct['configurable_options'] ?? []) as $option)
                <article>
                    <h3>{{ $option['label'] ?? 'Configurable option' }}</h3>
                    <p>{{ $option['attribute_code'] ?? 'attribute' }}</p>
                    <span>{{ implode(', ', $option['values'] ?? []) }}</span>
                </article>
            @endforeach
            @if (($selectedProduct['custom_options'] ?? []) === [] && ($selectedProduct['configurable_options'] ?? []) === [])
                <div class="product-state" role="status">
                    No custom options are attached to this product snapshot.
                </div>
            @endif
        </div>
    @else
        <div class="commerce-grid">
            <article>
                <span>Related products</span>
                <h3>{{ implode(', ', $selectedProduct['related_skus'] ?? ['none']) }}</h3>
            </article>
            <article>
                <span>Up-sells</span>
                <h3>{{ implode(', ', $selectedProduct['upsell_skus'] ?? ['none']) }}</h3>
            </article>
            <article>
                <span>Downloadable</span>
                <h3>{{ $downloadRows === [] ? 'No downloadable links' : 'Download permission tracked' }}</h3>
                @foreach ($downloadRows as $downloadRow)
                    @php($download = $downloadRow['payload'])
                    <p>{{ $download['title'] ?? 'Download' }} / {{ $download['permission'] ?? 'permission unavailable' }}</p>
                    <button type="button" disabled>Sample Download</button>
                @endforeach
            </article>
        </div>
    @endif
</section>
