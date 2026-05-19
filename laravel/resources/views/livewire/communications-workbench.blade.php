<section aria-label="Communications workbench" class="communications-card">
    <header class="communications-section-header">
        <p>Storefront communications</p>
        <h2>{{ $newsletterFeature->label }}, {{ $contactFeature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="communications-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Email, subject, SKU, message">
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
                <option value="subscribed">Subscribed</option>
                <option value="unsubscribed">Unsubscribed</option>
                <option value="pending">Pending</option>
                <option value="queued">Queued</option>
                <option value="sent">Sent</option>
                <option value="failed">Failed</option>
                <option value="blocked">Blocked</option>
                <option value="denied">Denied</option>
                <option value="valid">Valid</option>
                <option value="invalid">Invalid</option>
                <option value="problem">Problem</option>
                <option value="problem_report">Problem report</option>
            </select>
        </label>

        <label>
            Channel
            <select wire:model.live="channel">
                <option value="">All channels</option>
                <option value="newsletter">Newsletter</option>
                <option value="contact_form">Contact form</option>
                <option value="send_to_friend">Send to friend</option>
                <option value="product_alert">Product alert</option>
            </select>
        </label>

        <label>
            Role
            <select wire:model.live="role">
                <option value="customer">Customer</option>
                <option value="catalog">Catalog</option>
                <option value="read-only">Read only</option>
                <option value="denied">Denied</option>
            </select>
        </label>
    </div>

    <div class="communications-toolbar">
        <div aria-label="Communications summary" class="communications-summary">
            <span>{{ count($newsletterRows) }} newsletter rows</span>
            <span>{{ count($contactRows) }} contact rows</span>
            <span>{{ count($alertRows) }} alert rows</span>
            <span>{{ count($queueRows) }} queue rows</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Communications sections">
            <button type="button" wire:click="setSection('newsletter')" @class(['is-active' => $section === 'newsletter'])>Newsletter</button>
            <button type="button" wire:click="setSection('contact')" @class(['is-active' => $section === 'contact'])>Contact</button>
            <button type="button" wire:click="setSection('alerts')" @class(['is-active' => $section === 'alerts'])>Alerts</button>
            <button type="button" wire:click="setSection('queue')" @class(['is-active' => $section === 'queue'])>Queue</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="communications-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="communications-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewCommunications)
        <div class="communications-state communications-state-danger" role="alert">
            Permission denied for communications role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="communications-state" role="status">
            There are no {{ $section }} facts matching this communications scope.
        </div>
    @else
        <article class="communications-plan">
            <p>{{ \Illuminate\Support\Str::headline($mailPlan['kind']) }} plan</p>
            <h3>{{ $mailPlan['recipient'] }}</h3>
            <span>{{ $mailPlan['queue'] }} / {{ $mailPlan['email'] ? 'email enabled' : 'email disabled' }}</span>
        </article>

        <div class="communications-grid">
            @foreach ($currentRows as $row)
                <article wire:key="communications-row-{{ $section }}-{{ $row['store_view'] }}-{{ $row['entity_id'] }}" @class(['communications-problem-card' => $row['is_problem'] || $row['queue_status'] === 'failed'])>
                    <span>{{ $row['kind_label'] }} / {{ $row['status_label'] }} / {{ $row['queue_label'] }}</span>
                    <h3>{{ $row['title'] }}</h3>
                    <p>
                        @if ($row['recipient'] !== '' && $row['sender'] !== '')
                            {{ $row['recipient'] }} / {{ $row['sender'] }}
                        @else
                            {{ $row['recipient'] !== '' ? $row['recipient'] : $row['sender'] }}
                        @endif
                    </p>
                    @if ($row['product_sku'] !== '')
                        <strong>{{ $row['product_name'] }} / {{ $row['product_sku'] }}</strong>
                    @endif
                    <small>{{ $row['summary'] }}</small>
                    @if ($row['message'] !== '')
                        <em>{{ $row['message'] }}</em>
                    @endif
                    @if ($row['last_error'] !== '' && $row['last_error'] !== $row['message'])
                        <em>{{ $row['last_error'] }}</em>
                    @endif
                    <button type="button" disabled>{{ $section === 'queue' ? 'Inspect Queue' : 'Preview Message' }}</button>
                </article>
            @endforeach
        </div>
    @endif
</section>
