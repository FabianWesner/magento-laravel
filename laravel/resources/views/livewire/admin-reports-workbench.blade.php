<section aria-label="Admin reports workbench" class="reports-card">
    <header class="reports-section-header">
        <p>Admin reports</p>
        <h2>{{ $selectedDefinition->label }}</h2>
        <span>{{ implode(', ', $selectedDefinition->featureIds) }}</span>
    </header>

    <div class="reports-control-grid">
        <label>
            Report
            <select wire:model.live="reportKey" aria-label="Report selector">
                @foreach ($definitions as $key => $definition)
                    <option value="{{ $key }}">{{ $definition->label }}</option>
                @endforeach
            </select>
        </label>

        <label>
            From
            <input type="date" wire:model.live="fromDate">
        </label>

        <label>
            To
            <input type="date" wire:model.live="toDate">
        </label>

        <label>
            Store
            <select wire:model.live="storeId">
                <option value="">All stores</option>
                <option value="9001">Store 9001</option>
                <option value="9002">Store 9002</option>
            </select>
        </label>

        <label>
            Currency
            <select wire:model.live="currency">
                <option value="">All currencies</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
            </select>
        </label>

        <label>
            Role
            <select wire:model.live="role">
                <option value="reports">Reports</option>
                <option value="read-only">Read only</option>
                <option value="denied">Denied</option>
            </select>
        </label>
    </div>

    @if (! $canViewReports)
        <div class="reports-state reports-state-danger" role="alert">
            Permission denied for report role `{{ $role }}`.
        </div>
    @elseif ($result->count === 0)
        <div class="reports-state" role="status">
            Empty report state for {{ $selectedDefinition->label }} with the current filters.
        </div>
    @else
        <div class="reports-metrics" aria-label="Report totals">
            <div>
                <span>Total</span>
                <strong>{{ number_format($result->total, 2) }}</strong>
            </div>
            <div>
                <span>Rows</span>
                <strong>{{ $result->count }}</strong>
            </div>
            <div>
                <span>Currency</span>
                <strong>{{ $displayCurrency }}</strong>
            </div>
            <div>
                <span>Sort</span>
                <strong>{{ $result->toArray()['grid']['sort']['bucket'] }}</strong>
            </div>
        </div>

        <table class="reports-table">
            <thead>
                <tr>
                    <th scope="col">Bucket</th>
                    <th scope="col">Currency</th>
                    <th scope="col">Total</th>
                    <th scope="col">Rows</th>
                    <th scope="col">Average</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($result->rows as $row)
                    <tr>
                        <th scope="row">{{ $row['bucket'] }}</th>
                        <td>{{ $row['currency'] }}</td>
                        <td>{{ number_format($row['total'], 2) }}</td>
                        <td>{{ $row['count'] }}</td>
                        <td>{{ number_format($row['average'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <button type="button" wire:click="toggleCsv" class="reports-secondary-action">
            {{ $showCsv ? 'Hide CSV preview' : 'Show CSV preview' }}
        </button>

        @if ($showCsv)
            <pre aria-label="CSV preview">{{ $csvPreview }}</pre>
        @endif
    @endif
</section>
