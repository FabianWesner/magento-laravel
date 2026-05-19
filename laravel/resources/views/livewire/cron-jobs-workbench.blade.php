<section aria-label="Cron jobs workbench" class="cron-jobs-card">
    <header class="cron-jobs-section-header">
        <p>Admin cron operations</p>
        <h2>CJ-001 through CJ-025</h2>
        <span>Scheduler mappings, legacy run models, migration decisions, and attention states.</span>
    </header>

    <div class="cron-jobs-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Feature, model, schedule, status">
        </label>

        <label>
            Status
            <select wire:model.live="status">
                <option value="">All statuses</option>
                <option value="scheduled">Scheduled</option>
                <option value="bridge">Bridge</option>
                <option value="config_pending">Config pending</option>
                <option value="decision_pending">Decision pending</option>
                <option value="blocked">Blocked</option>
                <option value="attention">Needs attention</option>
            </select>
        </label>

        <label>
            Decision
            <select wire:model.live="decision">
                <option value="">All decisions</option>
                <option value="replace">Replace</option>
                <option value="bridge">Bridge</option>
                <option value="retire_or_bridge">Retire or bridge</option>
            </select>
        </label>

        <label>
            Domain
            <select wire:model.live="domain">
                <option value="">All domains</option>
                <option value="operations">Operations</option>
                <option value="integrations">Integrations</option>
                <option value="reports">Reports</option>
                <option value="commerce">Commerce</option>
                <option value="communications">Communications</option>
            </select>
        </label>

        <label>
            Role
            <select wire:model.live="role">
                <option value="scheduler">Scheduler</option>
                <option value="read-only">Read only</option>
                <option value="denied">Denied</option>
            </select>
        </label>
    </div>

    <div class="cron-jobs-toolbar">
        <div aria-label="Cron jobs summary" class="cron-jobs-summary">
            <span>{{ $summary['tracked'] }} tracked jobs</span>
            <span>{{ $summary['scheduled'] }} scheduled replacements</span>
            <span>{{ $summary['config_driven'] }} config-driven</span>
            <span>{{ $summary['bridge'] }} bridge decisions</span>
            <span>{{ $summary['attention'] }} attention rows</span>
        </div>
        <div role="toolbar" aria-label="Cron job sections">
            <button type="button" wire:click="setSection('jobs')" @class(['is-active' => $section === 'jobs'])>Jobs</button>
            <button type="button" wire:click="setSection('config')" @class(['is-active' => $section === 'config'])>Config</button>
            <button type="button" wire:click="setSection('reports')" @class(['is-active' => $section === 'reports'])>Reports</button>
            <button type="button" wire:click="setSection('problems')" @class(['is-active' => $section === 'problems'])>Problems</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="cron-jobs-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="cron-job-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewCronJobs)
        <div class="cron-jobs-state cron-jobs-state-danger" role="alert">
            Permission denied for cron role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="cron-jobs-state" role="status">
            There are no {{ $section }} jobs matching this cron scope.
        </div>
    @else
        <div class="cron-jobs-grid">
            @foreach ($currentRows as $job)
                <article wire:key="cron-job-{{ $section }}-{{ $job['feature_id'] }}" @class(['cron-jobs-problem-card' => $job['needs_attention']])>
                    <span>{{ $job['feature_id'] }} / {{ $job['domain_label'] }} / {{ $job['status_label'] }}</span>
                    <h3>{{ $job['name_label'] }}</h3>
                    <p>{{ $job['operator_note'] }}</p>
                    <strong>{{ $job['schedule'] }}</strong>
                    <small>Legacy model: {{ $job['legacy_model'] }}</small>
                    <small>Decision: {{ $job['decision_label'] }} / Risk: {{ $job['risk'] }}</small>
                    <small>Last run: {{ $job['last_run_at'] }}</small>
                    <em>Next run: {{ $job['next_run_at'] }}</em>
                    <button type="button" disabled>Inspect Only</button>
                </article>
            @endforeach
        </div>
    @endif
</section>
