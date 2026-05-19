<section aria-label="Admin newsletter and polls workbench" class="admin-newsletter-polls-card">
    <header class="admin-newsletter-polls-section-header">
        <p>Admin newsletter and polls diagnostics</p>
        <h2>{{ $newsletterFeature->label }}, {{ $pollFeature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="admin-newsletter-polls-controls">
        <label>
            Search
            <input type="search" wire:model.live="query" placeholder="Email, poll, answer, failure">
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
                <option value="sent">Sent</option>
                <option value="queued">Queued</option>
                <option value="failed">Failed</option>
                <option value="problem_report">Problem report</option>
                <option value="valid">Valid template</option>
                <option value="active">Active poll</option>
                <option value="closed">Closed poll</option>
                <option value="invalid">Invalid poll</option>
                <option value="problem">Problems</option>
            </select>
        </label>

        <label>
            Type
            <select wire:model.live="type">
                <option value="">All types</option>
                <option value="subscriber">Subscriber</option>
                <option value="template">Template</option>
                <option value="queue">Queue</option>
                <option value="poll">Poll</option>
                <option value="poll_answer">Poll answer</option>
            </select>
        </label>

        <label>
            Role
            <select wire:model.live="role">
                <option value="catalog">Catalog</option>
                <option value="read-only">Read only</option>
                <option value="full">Full</option>
                <option value="denied">Denied</option>
            </select>
        </label>
    </div>

    <div class="admin-newsletter-polls-toolbar">
        <div aria-label="Admin newsletter and polls summary" class="admin-newsletter-polls-summary">
            <span>{{ count($subscriberRows) }} subscribers</span>
            <span>{{ count($templateRows) }} templates</span>
            <span>{{ count($queueRows) }} queue rows</span>
            <span>{{ count($pollRows) }} polls</span>
            <span>{{ count($answerRows) }} answers</span>
            <span>{{ count($problemRows) }} problems</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Admin newsletter and polls sections">
            <button type="button" wire:click="setSection('subscribers')" @class(['is-active' => $section === 'subscribers'])>Subscribers</button>
            <button type="button" wire:click="setSection('templates')" @class(['is-active' => $section === 'templates'])>Templates</button>
            <button type="button" wire:click="setSection('queue')" @class(['is-active' => $section === 'queue'])>Queue</button>
            <button type="button" wire:click="setSection('polls')" @class(['is-active' => $section === 'polls'])>Polls</button>
            <button type="button" wire:click="setSection('answers')" @class(['is-active' => $section === 'answers'])>Answers</button>
            <button type="button" wire:click="setSection('problems')" @class(['is-active' => $section === 'problems'])>Problems</button>
        </div>
    </div>

    @if ($activeFilters !== [])
        <div class="admin-newsletter-polls-active-filters" aria-label="Active filters">
            @foreach ($activeFilters as $name => $value)
                <span wire:key="admin-newsletter-polls-filter-{{ $name }}">{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearFilters">Clear filters</button>
        </div>
    @endif

    @if (! $canViewNewsletterPolls)
        <div class="admin-newsletter-polls-state admin-newsletter-polls-state-danger" role="alert">
            Permission denied for admin newsletter polls role `{{ $role }}`.
        </div>
    @elseif ($currentRows === [])
        <div class="admin-newsletter-polls-state" role="status">
            There are no {{ $section }} facts matching this admin newsletter polls scope.
        </div>
    @else
        <div class="admin-newsletter-polls-grid">
            @foreach ($currentRows as $row)
                <article wire:key="admin-newsletter-polls-row-{{ $section }}-{{ $row['store_view'] }}-{{ $row['type'] }}-{{ $row['entity_id'] }}-{{ $loop->index }}" @class(['admin-newsletter-polls-problem-card' => $row['is_problem']])>
                    <span>{{ $row['type_label'] }} / {{ $row['status_label'] }} / {{ $row['queue_label'] }}</span>
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
