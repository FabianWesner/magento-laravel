<section aria-label="Customer commerce workbench" class="commerce-card">
    <header class="commerce-section-header">
        <p>Customer commerce</p>
        <h2>{{ $wishlistFeature->label }}, {{ $compareFeature->label }}, {{ $reviewFeature->label }}, {{ $tagFeature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="commerce-controls">
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
            Customer
            <select wire:model.live="customerId">
                <option value="">All customers</option>
                @foreach ($customerOptions as $id => $name)
                    <option wire:key="commerce-customer-option-{{ $id }}" value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </label>

        <label>
            Product
            <select wire:model.live="productSku">
                <option value="">All products</option>
                @foreach ($productOptions as $sku)
                    <option wire:key="commerce-product-option-{{ $sku }}" value="{{ $sku }}">{{ $sku }}</option>
                @endforeach
            </select>
        </label>

        <label>
            Status
            <select wire:model.live="status">
                <option value="">All states</option>
                <option value="active">Active</option>
                <option value="shared">Shared</option>
                <option value="empty_private">Empty private</option>
                <option value="empty_guest">Empty guest</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="disabled">Disabled</option>
            </select>
        </label>

        <label>
            Role
            <select wire:model.live="role">
                <option value="customer">Customer</option>
                <option value="read-only">Read only</option>
                <option value="denied">Denied</option>
            </select>
        </label>
    </div>

    <div class="commerce-toolbar">
        <div aria-label="Customer commerce summary" class="commerce-summary">
            <span>{{ count($wishlistRows) }} wishlist rows</span>
            <span>{{ count($compareRows) }} compare rows</span>
            <span>{{ count($reviewRows) }} review rows</span>
            <span>{{ count($tagRows) }} tag rows</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Customer commerce sections">
            <button type="button" wire:click="setSection('wishlist')" @class(['is-active' => $section === 'wishlist'])>Wishlist</button>
            <button type="button" wire:click="setSection('compare')" @class(['is-active' => $section === 'compare'])>Compare</button>
            <button type="button" wire:click="setSection('reviews')" @class(['is-active' => $section === 'reviews'])>Reviews</button>
            <button type="button" wire:click="setSection('tags')" @class(['is-active' => $section === 'tags'])>Tags</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="commerce-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="commerce-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewCommerce)
        <div class="commerce-state commerce-state-danger" role="alert">
            Permission denied for customer commerce role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="commerce-state" role="status">
            There are no {{ $section }} facts matching this customer commerce scope.
        </div>
    @elseif ($section === 'wishlist')
        <div class="commerce-grid">
            @foreach ($wishlistRows as $row)
                <article wire:key="wishlist-row-{{ $row['store_view'] }}-{{ $row['entity_id'] }}">
                    <span>{{ $row['customer_name'] }} / {{ $row['status_label'] }}</span>
                    <h3>{{ $row['is_shared'] ? 'Shared wishlist' : 'Wishlist' }}</h3>
                    <p>{{ $row['item_summary'] }}</p>
                    <small>{{ $row['item_count'] }} item rows / {{ $row['share_code'] === '' ? 'private' : $row['share_code'] }}</small>
                    @if ($row['body'] !== '')
                        <em>{{ $row['body'] }}</em>
                    @endif
                    <button type="button" disabled>Move to Cart</button>
                </article>
            @endforeach
        </div>
    @elseif ($section === 'compare')
        <div class="commerce-grid">
            @foreach ($compareRows as $row)
                <article wire:key="compare-row-{{ $row['store_view'] }}-{{ $row['entity_id'] }}">
                    <span>{{ $row['customer_name'] }} / {{ $row['status_label'] }}</span>
                    <h3>{{ $row['item_summary'] }}</h3>
                    <p>{{ $row['attribute_summary'] }}</p>
                    <small>{{ $row['item_count'] }} compared products</small>
                    @if ($row['denied_reason'] !== '')
                        <em>{{ $row['denied_reason'] }}</em>
                    @endif
                    <button type="button" disabled>Remove Selected</button>
                </article>
            @endforeach
        </div>
    @elseif ($section === 'reviews')
        <div class="commerce-grid">
            @foreach ($reviewRows as $row)
                <article wire:key="review-row-{{ $row['store_view'] }}-{{ $row['entity_id'] }}">
                    <span>{{ $row['customer_name'] }} / {{ $row['status_label'] }}</span>
                    <h3>{{ $row['title'] }}</h3>
                    <p>{{ $row['product_name'] }} / {{ $row['product_sku'] }}</p>
                    <strong>{{ $row['rating_summary'] }}</strong>
                    <small>{{ $row['rating_breakdown'] }}</small>
                    <em>{{ $row['body'] }}</em>
                </article>
            @endforeach
        </div>
    @else
        <div class="commerce-grid">
            @foreach ($tagRows as $row)
                <article wire:key="tag-row-{{ $row['store_view'] }}-{{ $row['entity_id'] }}">
                    <span>{{ $row['customer_name'] }} / {{ $row['status_label'] }}</span>
                    <h3>{{ $row['title'] }}</h3>
                    <p>{{ $row['product_name'] }} / {{ $row['product_sku'] }}</p>
                    <strong>{{ $row['uses_count'] }} uses</strong>
                    <small>{{ $row['created_at'] }}</small>
                </article>
            @endforeach
        </div>
    @endif
</section>
