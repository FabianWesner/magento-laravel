<section aria-label="Admin promotions workbench" class="admin-promotions-card">
    <header class="admin-promotions-section-header">
        <p>Admin promotions diagnostics</p>
        <h2>{{ $cartRuleFeature->label }}, {{ $catalogRuleFeature->label }}, {{ $couponFeature->label }}, {{ $reportFeature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="admin-promotions-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Rule, coupon, report">
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
                <option value="scheduled">Scheduled</option>
                <option value="inactive">Inactive</option>
                <option value="expired">Expired</option>
                <option value="generated">Generated</option>
                <option value="exhausted">Exhausted</option>
                <option value="aggregated">Aggregated</option>
                <option value="stale">Stale</option>
                <option value="invalid">Invalid</option>
                <option value="invalid_condition">Invalid condition</option>
                <option value="failed">Failed</option>
                <option value="problem">Problems</option>
            </select>
        </label>

        <label>
            Type
            <select wire:model.live="type">
                <option value="">All types</option>
                <option value="cart_rule">Cart rule</option>
                <option value="catalog_rule">Catalog rule</option>
                <option value="coupon">Coupon</option>
                <option value="report">Report</option>
            </select>
        </label>

        <label>
            Role
            <select wire:model.live="role">
                <option value="sales">Sales</option>
                <option value="catalog">Catalog</option>
                <option value="read-only">Read only</option>
                <option value="full">Full</option>
                <option value="denied">Denied</option>
            </select>
        </label>
    </div>

    <div class="admin-promotions-toolbar">
        <div aria-label="Admin promotions summary" class="admin-promotions-summary">
            <span>{{ count($cartRuleRows) }} cart rules</span>
            <span>{{ count($catalogRuleRows) }} catalog rules</span>
            <span>{{ count($couponRows) }} coupons</span>
            <span>{{ count($reportRows) }} reports</span>
            <span>{{ count($problemRows) }} problems</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Admin promotions sections">
            <button type="button" wire:click="setSection('cart_rules')" @class(['is-active' => $section === 'cart_rules'])>Cart rules</button>
            <button type="button" wire:click="setSection('catalog_rules')" @class(['is-active' => $section === 'catalog_rules'])>Catalog rules</button>
            <button type="button" wire:click="setSection('coupons')" @class(['is-active' => $section === 'coupons'])>Coupons</button>
            <button type="button" wire:click="setSection('reports')" @class(['is-active' => $section === 'reports'])>Reports</button>
            <button type="button" wire:click="setSection('problems')" @class(['is-active' => $section === 'problems'])>Problems</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="admin-promotions-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="admin-promotions-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewPromotions)
        <div class="admin-promotions-state admin-promotions-state-danger" role="alert">
            Permission denied for admin promotions role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="admin-promotions-state" role="status">
            There are no {{ str_replace('_', ' ', $section) }} facts matching this admin promotions scope.
        </div>
    @else
        <div class="admin-promotions-grid">
            @foreach ($currentRows as $row)
                <article wire:key="admin-promotions-row-{{ $section }}-{{ $row['store_view'] }}-{{ $row['type'] }}-{{ $row['entity_id'] }}-{{ $loop->index }}" @class(['admin-promotions-problem-card' => $row['is_problem']])>
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
