<section aria-label="Tax currency workbench" class="tax-currency-card">
    <header class="tax-currency-section-header">
        <p>Admin tax and currency</p>
        <h2>{{ $taxFeature->label }}, {{ $currencyFeature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="tax-currency-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Rate, rule, currency, job, error">
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
                <option value="valid">Valid</option>
                <option value="invalid">Invalid</option>
                <option value="current">Current</option>
                <option value="stale">Stale</option>
                <option value="scheduled">Scheduled</option>
                <option value="failed">Failed</option>
                <option value="aggregated">Aggregated</option>
                <option value="problem">Problem</option>
            </select>
        </label>

        <label>
            Type
            <select wire:model.live="type">
                <option value="">All types</option>
                <option value="tax">Tax</option>
                <option value="currency">Currency</option>
                <option value="class">Class</option>
                <option value="rate">Rate</option>
                <option value="rule">Rule</option>
                <option value="calculation">Calculation</option>
                <option value="report">Report</option>
                <option value="config">Config</option>
                <option value="currency_rate">Currency rate</option>
                <option value="symbol">Symbol</option>
                <option value="job">Job</option>
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

    <div class="tax-currency-toolbar">
        <div aria-label="Tax currency summary" class="tax-currency-summary">
            <span>{{ count($taxRows) }} tax rows</span>
            <span>{{ count($currencyRows) }} currency rows</span>
            <span>{{ count($jobRows) }} job rows</span>
            <span>{{ count($problemRows) }} problem rows</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Tax currency sections">
            <button type="button" wire:click="setSection('tax')" @class(['is-active' => $section === 'tax'])>Tax</button>
            <button type="button" wire:click="setSection('currency')" @class(['is-active' => $section === 'currency'])>Currency</button>
            <button type="button" wire:click="setSection('jobs')" @class(['is-active' => $section === 'jobs'])>Jobs</button>
            <button type="button" wire:click="setSection('problems')" @class(['is-active' => $section === 'problems'])>Problems</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="tax-currency-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="tax-currency-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewTaxCurrency)
        <div class="tax-currency-state tax-currency-state-danger" role="alert">
            Permission denied for tax currency role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="tax-currency-state" role="status">
            There are no {{ $section }} facts matching this tax currency scope.
        </div>
    @else
        <div class="tax-currency-grid">
            @foreach ($currentRows as $row)
                <article wire:key="tax-currency-row-{{ $section }}-{{ $row['store_view'] }}-{{ $row['entity_id'] }}" @class(['tax-currency-problem-card' => $row['is_problem']])>
                    <span>{{ $row['domain_label'] }} / {{ $row['kind_label'] }} / {{ $row['status_label'] }}</span>
                    <h3>{{ $row['title'] }}</h3>
                    <p>{{ $row['summary'] }}</p>
                    @if ($row['primary_value'] !== '')
                        <strong>{{ $row['primary_value'] }}</strong>
                    @endif
                    @if ($row['detail'] !== '')
                        <small>{{ $row['detail'] }}</small>
                    @endif
                    @if ($row['scope'] !== '')
                        <small>{{ $row['scope'] }}</small>
                    @endif
                    @if ($row['schedule'] !== '')
                        <em>{{ $row['schedule'] }}</em>
                    @endif
                    @if ($row['error'] !== '')
                        <em>{{ $row['error'] }}</em>
                    @endif
                    <button type="button" disabled>{{ $section === 'jobs' ? 'Inspect Job' : 'Preview Rule' }}</button>
                </article>
            @endforeach
        </div>
    @endif
</section>
