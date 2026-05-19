<section aria-label="Admin sales fulfillment workbench" class="admin-sales-fulfillment-card">
    <header class="admin-sales-fulfillment-section-header">
        <p>Admin sales fulfillment diagnostics</p>
        <h2>{{ $orderFeature->label }}, {{ $invoiceFeature->label }}, {{ $shipmentFeature->label }}, {{ $creditMemoFeature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="admin-sales-fulfillment-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Order, customer, transaction">
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
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="complete">Complete</option>
                <option value="closed">Closed</option>
                <option value="canceled">Canceled</option>
                <option value="holded">Holded</option>
                <option value="payment_review">Payment review</option>
                <option value="paid">Paid</option>
                <option value="partial">Partial</option>
                <option value="shipped">Shipped</option>
                <option value="tracking_pending">Tracking pending</option>
                <option value="refunded">Refunded</option>
                <option value="offline_refund">Offline refund</option>
                <option value="captured">Captured</option>
                <option value="authorization">Authorization</option>
                <option value="failed">Failed</option>
                <option value="problem">Problems</option>
            </select>
        </label>

        <label>
            Type
            <select wire:model.live="type">
                <option value="">All types</option>
                <option value="order">Order</option>
                <option value="invoice">Invoice</option>
                <option value="shipment">Shipment</option>
                <option value="credit_memo">Credit memo</option>
                <option value="transaction">Transaction</option>
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

    <div class="admin-sales-fulfillment-toolbar">
        <div aria-label="Admin sales fulfillment summary" class="admin-sales-fulfillment-summary">
            <span>{{ count($orderRows) }} orders</span>
            <span>{{ count($invoiceRows) }} invoices</span>
            <span>{{ count($shipmentRows) }} shipments</span>
            <span>{{ count($creditMemoRows) }} credit memos</span>
            <span>{{ count($transactionRows) }} transactions</span>
            <span>{{ count($problemRows) }} problems</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Admin sales fulfillment sections">
            <button type="button" wire:click="setSection('orders')" @class(['is-active' => $section === 'orders'])>Orders</button>
            <button type="button" wire:click="setSection('invoices')" @class(['is-active' => $section === 'invoices'])>Invoices</button>
            <button type="button" wire:click="setSection('shipments')" @class(['is-active' => $section === 'shipments'])>Shipments</button>
            <button type="button" wire:click="setSection('credit_memos')" @class(['is-active' => $section === 'credit_memos'])>Credit memos</button>
            <button type="button" wire:click="setSection('transactions')" @class(['is-active' => $section === 'transactions'])>Transactions</button>
            <button type="button" wire:click="setSection('problems')" @class(['is-active' => $section === 'problems'])>Problems</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="admin-sales-fulfillment-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="admin-sales-fulfillment-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewSalesFulfillment)
        <div class="admin-sales-fulfillment-state admin-sales-fulfillment-state-danger" role="alert">
            Permission denied for admin sales fulfillment role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="admin-sales-fulfillment-state" role="status">
            There are no {{ str_replace('_', ' ', $section) }} facts matching this admin sales fulfillment scope.
        </div>
    @else
        <div class="admin-sales-fulfillment-grid">
            @foreach ($currentRows as $row)
                <article wire:key="admin-sales-fulfillment-row-{{ $section }}-{{ $row['store_view'] }}-{{ $row['type'] }}-{{ $row['entity_id'] }}-{{ $loop->index }}" @class(['admin-sales-fulfillment-problem-card' => $row['is_problem']])>
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
