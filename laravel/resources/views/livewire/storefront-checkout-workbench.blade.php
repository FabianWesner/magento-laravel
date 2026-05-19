<section aria-label="Storefront checkout diagnostics" class="storefront-checkout-card">
    <header class="storefront-checkout-section-header">
        <p>Storefront checkout diagnostics</p>
        <h2>{{ $stepFeature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="storefront-checkout-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Quote, payment, agreement, method">
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
                <option value="invalid_address">Invalid address</option>
                <option value="method_required">Method required</option>
                <option value="agreement_required">Agreement required</option>
                <option value="payment_review">Payment review</option>
                <option value="failed_payment">Failed payment</option>
                <option value="blocked">Blocked</option>
                <option value="addresses">Addresses</option>
                <option value="methods">Methods</option>
                <option value="overview">Overview</option>
                <option value="problem">Problems only</option>
            </select>
        </label>

        <label>
            Type
            <select wire:model.live="type">
                <option value="">All types</option>
                <option value="step">Checkout step</option>
                <option value="payment">Payment</option>
                <option value="review">Review</option>
                <option value="multishipping">Multishipping</option>
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

    <div class="storefront-checkout-toolbar">
        <div aria-label="Checkout diagnostics summary" class="storefront-checkout-summary">
            <span>{{ count($stepRows) }} steps</span>
            <span>{{ count($paymentRows) }} payments</span>
            <span>{{ count($reviewRows) }} review rows</span>
            <span>{{ count($multishippingRows) }} multishipping rows</span>
            <span>{{ count($problemRows) }} problems</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Checkout diagnostics sections">
            <button type="button" wire:click="setSection('steps')" @class(['is-active' => $section === 'steps'])>Steps</button>
            <button type="button" wire:click="setSection('payments')" @class(['is-active' => $section === 'payments'])>Payments</button>
            <button type="button" wire:click="setSection('review')" @class(['is-active' => $section === 'review'])>Review</button>
            <button type="button" wire:click="setSection('multishipping')" @class(['is-active' => $section === 'multishipping'])>Multishipping</button>
            <button type="button" wire:click="setSection('problems')" @class(['is-active' => $section === 'problems'])>Problems</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="storefront-checkout-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span>{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewCheckout)
        <div class="storefront-checkout-state storefront-checkout-state-danger" role="alert">
            Permission denied for storefront checkout role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="storefront-checkout-state" role="status">
            There are no {{ str_replace('_', ' ', $section) }} facts matching this checkout scope.
        </div>
    @else
        <div class="storefront-checkout-grid">
            @foreach ($currentRows as $row)
                <article @class(['storefront-checkout-row', 'is-problem' => $row['is_problem']])>
                    <div class="storefront-checkout-row-top">
                        <div>
                            <p>{{ $row['type_label'] }} / {{ $row['status_label'] }} / {{ $row['store_view'] }}</p>
                            <h3>{{ $row['title'] }}</h3>
                            <span>{{ $row['subtitle'] }}</span>
                        </div>
                        @if ($row['metric'] !== '')
                            <strong>{{ $row['metric'] }}</strong>
                        @endif
                    </div>

                    <dl class="storefront-checkout-facts">
                        <div>
                            <dt>Summary</dt>
                            <dd>{{ $row['summary'] !== '' ? $row['summary'] : 'No summary captured' }}</dd>
                        </div>
                        <div>
                            <dt>Detail</dt>
                            <dd>{{ $row['detail'] !== '' ? $row['detail'] : 'No detail captured' }}</dd>
                        </div>
                    </dl>

                    <div class="storefront-checkout-actions" aria-label="Read-only checkout actions">
                        <button type="button" disabled>{{ $row['action_label'] }}</button>
                        <button type="button" disabled>Save Billing</button>
                        <button type="button" disabled>Save Shipping</button>
                        <button type="button" disabled>Save Payment</button>
                        <button type="button" disabled>Place Order</button>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
