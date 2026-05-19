<section aria-label="Admin customer management workbench" class="admin-customer-card">
    <header class="admin-customer-section-header">
        <p>Admin customer diagnostics</p>
        <h2>{{ $customerFeature->label }}, {{ $addressFeature->label }}, {{ $reviewFeature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="admin-customer-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Name, email, SKU, reason">
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
                <option value="inactive">Inactive</option>
                <option value="subscribed">Subscribed</option>
                <option value="unsubscribed">Unsubscribed</option>
                <option value="shared">Shared</option>
                <option value="empty_private">Empty private</option>
                <option value="empty_guest">Empty guest</option>
                <option value="approved">Approved</option>
                <option value="pending">Pending</option>
                <option value="not_shared">Not shared</option>
                <option value="guest_session_expired">Guest session expired</option>
                <option value="problem">Problems</option>
            </select>
        </label>

        <label>
            Type
            <select wire:model.live="type">
                <option value="">All types</option>
                <option value="customer">Customer</option>
                <option value="address">Address</option>
                <option value="wishlist">Wishlist</option>
                <option value="compare">Compare</option>
                <option value="review">Review</option>
                <option value="tag">Tag</option>
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

    <div class="admin-customer-toolbar">
        <div aria-label="Admin customer summary" class="admin-customer-summary">
            <span>{{ count($customerRows) }} customers</span>
            <span>{{ count($addressRows) }} addresses</span>
            <span>{{ count($activityRows) }} activity rows</span>
            <span>{{ count($moderationRows) }} moderation rows</span>
            <span>{{ count($problemRows) }} problems</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Admin customer sections">
            <button type="button" wire:click="setSection('customers')" @class(['is-active' => $section === 'customers'])>Customers</button>
            <button type="button" wire:click="setSection('addresses')" @class(['is-active' => $section === 'addresses'])>Addresses</button>
            <button type="button" wire:click="setSection('activity')" @class(['is-active' => $section === 'activity'])>Activity</button>
            <button type="button" wire:click="setSection('moderation')" @class(['is-active' => $section === 'moderation'])>Moderation</button>
            <button type="button" wire:click="setSection('problems')" @class(['is-active' => $section === 'problems'])>Problems</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="admin-customer-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="admin-customer-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewCustomers)
        <div class="admin-customer-state admin-customer-state-danger" role="alert">
            Permission denied for admin customer role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="admin-customer-state" role="status">
            There are no {{ $section }} facts matching this admin customer scope.
        </div>
    @else
        <div class="admin-customer-grid">
            @foreach ($currentRows as $row)
                <article wire:key="admin-customer-row-{{ $section }}-{{ $row['store_view'] }}-{{ $row['type'] }}-{{ $row['entity_id'] }}-{{ $loop->index }}" @class(['admin-customer-problem-card' => $row['is_problem']])>
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
                    <small>Customer {{ $row['customer_id'] !== '' ? $row['customer_id'] : 'guest' }} / Store {{ $row['store_id'] }} / {{ $row['store_view'] }}</small>
                    <button type="button" disabled>{{ $row['action_label'] }}</button>
                </article>
            @endforeach
        </div>
    @endif
</section>
