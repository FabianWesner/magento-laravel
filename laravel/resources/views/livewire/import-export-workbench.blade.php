<section aria-label="Import export workbench" class="import-export-card">
    <header class="import-export-section-header">
        <p>Admin operations</p>
        <h2>{{ $importExportFeature->label }}, {{ $dataflowFeature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="import-export-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Profile, file, batch, error">
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
                <option value="validated">Validated</option>
                <option value="generated">Generated</option>
                <option value="scheduled">Scheduled</option>
                <option value="completed">Completed</option>
                <option value="processing">Processing</option>
                <option value="failed">Failed</option>
                <option value="blocked">Blocked</option>
                <option value="problem">Problem</option>
            </select>
        </label>

        <label>
            Operation
            <select wire:model.live="operation">
                <option value="">All operations</option>
                <option value="import">Import</option>
                <option value="export">Export</option>
                <option value="profile">Profile</option>
            </select>
        </label>

        <label>
            Entity
            <select wire:model.live="entity">
                <option value="">All entities</option>
                <option value="catalog_product">Catalog product</option>
                <option value="customer">Customer</option>
                <option value="stock">Stock</option>
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

    <div class="import-export-toolbar">
        <div aria-label="Import export summary" class="import-export-summary">
            <span>{{ count($importRows) }} import rows</span>
            <span>{{ count($exportRows) }} export rows</span>
            <span>{{ count($profileRows) }} profile rows</span>
            <span>{{ count($fileRows) }} file rows</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Import export sections">
            <button type="button" wire:click="setSection('imports')" @class(['is-active' => $section === 'imports'])>Imports</button>
            <button type="button" wire:click="setSection('exports')" @class(['is-active' => $section === 'exports'])>Exports</button>
            <button type="button" wire:click="setSection('profiles')" @class(['is-active' => $section === 'profiles'])>Profiles</button>
            <button type="button" wire:click="setSection('files')" @class(['is-active' => $section === 'files'])>Files</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="import-export-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="import-export-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewOperations)
        <div class="import-export-state import-export-state-danger" role="alert">
            Permission denied for import export role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="import-export-state" role="status">
            There are no {{ $section }} facts matching this import export scope.
        </div>
    @else
        <article class="import-export-plan">
            <p>Validation plan</p>
            <h3>{{ $validationPlan['format'] }} / {{ $validationPlan['validation'] }}</h3>
            <span>{{ $validationPlan['rows'] }} row sample / {{ $validationPlan['batch'] ? 'batch ready' : 'single row' }} / {{ $errorPlan['error_file'] }}</span>
        </article>

        <div class="import-export-grid">
            @foreach ($currentRows as $row)
                <article wire:key="import-export-row-{{ $section }}-{{ $row['store_view'] }}-{{ $row['entity_id'] }}" @class(['import-export-problem-card' => $row['is_problem']])>
                    <span>{{ $row['operation_label'] }} / {{ $row['entity_label'] }} / {{ $row['status_label'] }}</span>
                    <h3>{{ $row['title'] }}</h3>
                    <p>{{ $row['summary'] }}</p>
                    <strong>{{ $row['profile_name'] }}{{ $row['batch_id'] !== '' ? ' / '.$row['batch_id'] : '' }}</strong>
                    <small>{{ $row['format'] }} / {{ $row['rows_processed'] }} of {{ $row['rows_total'] }} rows / {{ $row['rows_failed'] }} failed</small>
                    @if ($row['file_path'] !== '')
                        <em>{{ $row['file_path'] }}</em>
                    @endif
                    @if ($row['adapter'] !== '')
                        <small>{{ $row['adapter'] }}</small>
                    @endif
                    @if ($row['schedule'] !== '')
                        <small>{{ $row['schedule'] }}</small>
                    @endif
                    @if ($row['errors'] !== '')
                        <em>{{ $row['errors'] }}</em>
                    @endif
                    <button type="button" disabled>{{ $section === 'files' ? 'Inspect File' : 'Preview Batch' }}</button>
                </article>
            @endforeach
        </div>
    @endif
</section>
