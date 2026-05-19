<section aria-label="Storefront cart diagnostics" class="storefront-cart-card">
    <header class="storefront-cart-section-header">
        <p>Storefront cart diagnostics</p>
        <h2>{{ $quoteFeature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="storefront-cart-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Quote, SKU, coupon, carrier">
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
                <option value="customer">Customer</option>
                <option value="expired">Expired</option>
                <option value="valid">Valid item</option>
                <option value="invalid_quantity">Invalid quantity</option>
                <option value="low_stock">Low stock</option>
                <option value="backordered">Backordered</option>
                <option value="out_of_stock">Out of stock</option>
                <option value="collected">Collected totals</option>
                <option value="free_shipping">Free shipping</option>
                <option value="stale">Stale</option>
                <option value="available">Available</option>
                <option value="unavailable">Unavailable</option>
                <option value="virtual_only">Virtual only</option>
                <option value="problem">Problems only</option>
            </select>
        </label>

        <label>
            Type
            <select wire:model.live="type">
                <option value="">All types</option>
                <option value="quote">Quote</option>
                <option value="item">Cart item</option>
                <option value="total">Totals</option>
                <option value="shipping">Shipping</option>
            </select>
        </label>

        <label>
            Role
            <select wire:model.live="role">
                <option value="customer">Customer</option>
                <option value="read-only">Read only</option>
                <option value="full">Full</option>
                <option value="denied">Denied</option>
            </select>
        </label>
    </div>

    <div class="storefront-cart-toolbar">
        <div aria-label="Cart diagnostics summary" class="storefront-cart-summary">
            <span>{{ count($quoteRows) }} quotes</span>
            <span>{{ count($itemRows) }} items</span>
            <span>{{ count($totalRows) }} totals</span>
            <span>{{ count($shippingRows) }} shipping rates</span>
            <span>{{ count($problemRows) }} problems</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Cart diagnostics sections">
            <button type="button" wire:click="setSection('quotes')" @class(['is-active' => $section === 'quotes'])>Quotes</button>
            <button type="button" wire:click="setSection('items')" @class(['is-active' => $section === 'items'])>Items</button>
            <button type="button" wire:click="setSection('totals')" @class(['is-active' => $section === 'totals'])>Totals</button>
            <button type="button" wire:click="setSection('shipping')" @class(['is-active' => $section === 'shipping'])>Shipping</button>
            <button type="button" wire:click="setSection('problems')" @class(['is-active' => $section === 'problems'])>Problems</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="storefront-cart-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span>{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewCart)
        <div class="storefront-cart-state storefront-cart-state-danger" role="alert">
            Permission denied for storefront cart role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="storefront-cart-state" role="status">
            There are no {{ str_replace('_', ' ', $section) }} facts matching this cart scope.
        </div>
    @else
        <div class="storefront-cart-grid">
            @foreach ($currentRows as $row)
                <article @class(['storefront-cart-row', 'is-problem' => $row['is_problem']])>
                    <div class="storefront-cart-row-top">
                        <div>
                            <p>{{ $row['type_label'] }} / {{ $row['status_label'] }} / {{ $row['store_view'] }}</p>
                            <h3>{{ $row['title'] }}</h3>
                            <span>{{ $row['subtitle'] }}</span>
                        </div>
                        @if ($row['metric'] !== '')
                            <strong>{{ $row['metric'] }}</strong>
                        @endif
                    </div>

                    <dl class="storefront-cart-facts">
                        <div>
                            <dt>Summary</dt>
                            <dd>{{ $row['summary'] !== '' ? $row['summary'] : 'No summary captured' }}</dd>
                        </div>
                        <div>
                            <dt>Detail</dt>
                            <dd>{{ $row['detail'] !== '' ? $row['detail'] : 'No detail captured' }}</dd>
                        </div>
                    </dl>

                    <div class="storefront-cart-actions" aria-label="Read-only cart actions">
                        <button type="button" disabled>{{ $row['action_label'] }}</button>
                        <button type="button" disabled>Update Quantity</button>
                        <button type="button" disabled>Apply Coupon</button>
                        <button type="button" disabled>Estimate Shipping</button>
                        <button type="button" disabled>Proceed to Checkout</button>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
