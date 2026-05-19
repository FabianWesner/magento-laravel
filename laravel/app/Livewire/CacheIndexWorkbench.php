<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Policies\Modernization\Domain\DomainPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class CacheIndexWorkbench extends Component
{
    public string $storeView = '';

    public string $storeId = '';

    public string $query = '';

    public string $status = '';

    public string $type = '';

    public string $section = 'cache';

    public string $role = 'catalog';

    public function setSection(string $section): void
    {
        if (! in_array($section, ['cache', 'index', 'cron', 'locks'], true)) {
            return;
        }

        $this->section = $section;
    }

    public function clearFilters(): void
    {
        $this->storeView = '';
        $this->storeId = '';
        $this->query = '';
        $this->status = '';
        $this->type = '';
        $this->section = 'cache';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $this->storeId = $this->allowedStoreId();
        $this->storeView = $this->allowedStoreView();
        $this->status = $this->allowedStatus();
        $this->type = $this->allowedType();
        $this->section = $this->allowedSection();

        $canViewOperations = $this->canViewOperations($policy);
        $filters = $this->snapshotFilters();
        $cacheSnapshot = $canViewOperations ? $queryService->snapshot('cache', $filters) : null;
        $indexSnapshot = $canViewOperations ? $queryService->snapshot('index', $filters) : null;

        $cacheRows = $this->filterRows($this->decorateRows($this->snapshotRows($cacheSnapshot), 'cache'));
        $indexRows = $this->filterRows($this->decorateRows($this->snapshotRows($indexSnapshot), 'index'));
        $cronRows = $this->filterRows($this->cronRows([...$cacheRows, ...$indexRows]));
        $lockRows = $this->filterRows($this->lockRows([...$cacheRows, ...$indexRows]));

        return view('livewire.cache-index-workbench', [
            'cacheFeature' => $catalog->get('cache'),
            'indexFeature' => $catalog->get('index'),
            'featureIds' => array_values(array_unique([
                ...$catalog->get('cache')->featureIds,
                ...$catalog->get('index')->featureIds,
            ])),
            'canViewOperations' => $canViewOperations,
            'cacheRows' => $cacheRows,
            'indexRows' => $indexRows,
            'cronRows' => $cronRows,
            'lockRows' => $lockRows,
            'currentRows' => $this->currentRows($cacheRows, $indexRows, $cronRows, $lockRows),
            'activeFilters' => $this->activeFilters(),
            'snapshotStoreView' => $cacheSnapshot['store_view'] ?? null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotFilters(): array
    {
        return array_filter([
            'store_id' => $this->allowedStoreId(),
            'store_view' => $this->allowedStoreView(),
        ], fn (mixed $value): bool => $value !== '' && $value !== null);
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return list<array<string, mixed>>
     */
    private function snapshotRows(?array $snapshot): array
    {
        return $snapshot['payload']['rows'] ?? [];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decorateRows(array $rows, string $domain): array
    {
        return collect($rows)
            ->map(fn (array $row): array => $this->decorateRow($row, $domain))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function decorateRow(array $row, string $domain): array
    {
        $payload = $row['payload'] ?? [];
        $cron = $this->nestedArray($payload, 'cron');
        $lock = $this->nestedArray($payload, 'lock');
        $status = $this->status($payload);
        $kind = $this->kind($payload, $domain);
        $isStale = (bool) ($payload['is_stale'] ?? $payload['requires_reindex'] ?? false);
        $isLocked = (bool) ($payload['is_locked'] ?? $lock['active'] ?? false);
        $failureReason = $this->firstString($payload['failure_reason'] ?? null, $payload['last_error'] ?? null, $lock['failure_reason'] ?? null);

        return [
            'domain' => $domain,
            'kind' => $kind,
            'kind_label' => Str::headline($kind),
            'entity_id' => (int) ($row['entity_id'] ?? 0),
            'store_id' => (int) ($row['store_id'] ?? 0),
            'store_view' => (string) ($row['store_view'] ?? ''),
            'title' => $this->title($payload, $kind),
            'status' => $status,
            'status_label' => Str::headline($status),
            'is_stale' => $isStale,
            'freshness_label' => $isStale ? 'Stale' : 'Fresh',
            'is_locked' => $isLocked,
            'lock_owner' => $this->firstString($payload['lock_owner'] ?? null, $lock['owner'] ?? null),
            'failure_reason' => $failureReason,
            'is_problem' => $isStale || $isLocked || $failureReason !== '' || in_array($status, ['invalidated', 'reindex_required', 'processing', 'error'], true),
            'schedule' => $this->firstString($payload['schedule'] ?? null, $cron['schedule'] ?? null),
            'cron_job' => $this->firstString($payload['cron_job'] ?? null, $cron['job'] ?? null),
            'last_run' => $this->firstString($payload['last_run_at'] ?? null, $cron['last_run_at'] ?? null),
            'next_run' => $this->firstString($payload['next_run_at'] ?? null, $cron['next_run_at'] ?? null),
            'summary' => $this->summary($payload, $kind),
            'detail' => $this->detail($payload),
            'tags' => $this->valueSummary($payload['tags'] ?? $payload['cache_tags'] ?? []),
            'affected' => $this->affected($payload),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function kind(array $payload, string $domain): string
    {
        return (string) ($payload['operation_type'] ?? $payload['kind'] ?? $payload['cache_type'] ?? $payload['process_code'] ?? $domain);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function title(array $payload, string $kind): string
    {
        return $this->firstString(
            $payload['title'] ?? null,
            $payload['name'] ?? null,
            $payload['label'] ?? null,
            $payload['process_code'] ?? null,
            $payload['cache_type'] ?? null,
            Str::headline($kind),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function status(array $payload): string
    {
        return (string) ($payload['status'] ?? $payload['state'] ?? 'ready');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function summary(array $payload, string $kind): string
    {
        $ui = $this->nestedArray($payload, 'ui');

        return $this->firstString(
            $ui['summary'] ?? null,
            $payload['summary'] ?? null,
            $payload['description'] ?? null,
            Str::headline($kind),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function detail(array $payload): string
    {
        return $this->valueSummary(array_filter([
            'mode' => $payload['mode'] ?? null,
            'frontend' => $payload['frontend'] ?? null,
            'event_count' => $payload['event_count'] ?? null,
            'stale_reason' => $payload['stale_reason'] ?? null,
            'retained_decision' => $this->nestedArray($payload, 'compiler')['retained_decision'] ?? null,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function affected(array $payload): string
    {
        return $this->valueSummary($payload['affected_entities'] ?? $payload['affected_paths'] ?? $payload['actions'] ?? []);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function cronRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row): bool => $row['cron_job'] !== '' || $row['schedule'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function lockRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row): bool => $row['is_locked'] || $row['failure_reason'] !== '' || $row['is_stale'])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function filterRows(array $rows): array
    {
        $query = Str::lower(trim(Str::substr($this->query, 0, 128)));
        $status = $this->allowedStatus();
        $type = $this->allowedType();

        return collect($rows)
            ->filter(function (array $row) use ($query, $status, $type): bool {
                if ($status !== '' && $row['status'] !== $status && ($status !== 'stale' || ! $row['is_stale']) && ($status !== 'locked' || ! $row['is_locked'])) {
                    return false;
                }

                if ($type !== ''
                    && $row['kind'] !== $type
                    && $row['domain'] !== $type
                    && ($type !== 'cron' || $row['cron_job'] === '')
                ) {
                    return false;
                }

                if ($query === '') {
                    return true;
                }

                return Str::contains($this->rowHaystack($row), $query);
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowHaystack(array $row): string
    {
        return Str::lower(implode(' ', array_filter([
            $row['kind_label'] ?? '',
            $row['title'] ?? '',
            $row['status_label'] ?? '',
            $row['freshness_label'] ?? '',
            $row['lock_owner'] ?? '',
            $row['failure_reason'] ?? '',
            $row['cron_job'] ?? '',
            $row['schedule'] ?? '',
            $row['summary'] ?? '',
            $row['detail'] ?? '',
            $row['tags'] ?? '',
            $row['affected'] ?? '',
        ])));
    }

    /**
     * @param  list<array<string, mixed>>  $cacheRows
     * @param  list<array<string, mixed>>  $indexRows
     * @param  list<array<string, mixed>>  $cronRows
     * @param  list<array<string, mixed>>  $lockRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $cacheRows, array $indexRows, array $cronRows, array $lockRows): array
    {
        return match ($this->allowedSection()) {
            'index' => $indexRows,
            'cron' => $cronRows,
            'locks' => $lockRows,
            default => $cacheRows,
        };
    }

    /**
     * @return array<string, string>
     */
    private function activeFilters(): array
    {
        return array_filter([
            'store' => $this->allowedStoreView(),
            'store_id' => $this->allowedStoreId(),
            'query' => trim(Str::substr($this->query, 0, 128)),
            'status' => $this->allowedStatus(),
            'type' => $this->allowedType(),
        ], fn (string $value): bool => $value !== '');
    }

    private function allowedStoreId(): string
    {
        return in_array($this->storeId, ['', '9001', '9002'], true) ? $this->storeId : '';
    }

    private function allowedStoreView(): string
    {
        return in_array($this->storeView, ['', 'default', 'de'], true) ? $this->storeView : '';
    }

    private function allowedStatus(): string
    {
        return in_array($this->status, [
            '',
            'ready',
            'clean',
            'valid',
            'invalidated',
            'stale',
            'reindex_required',
            'processing',
            'error',
            'disabled',
            'locked',
        ], true) ? $this->status : '';
    }

    private function allowedType(): string
    {
        return in_array($this->type, ['', 'cache', 'index', 'compiler', 'cron'], true) ? $this->type : '';
    }

    private function allowedSection(): string
    {
        return in_array($this->section, ['cache', 'index', 'cron', 'locks'], true) ? $this->section : 'cache';
    }

    private function canViewOperations(DomainPolicy $policy): bool
    {
        if (! app()->environment(['local', 'testing'])) {
            return false;
        }

        return $policy->viewDiagnostics($this->fixtureUser());
    }

    private function fixtureUser(): User
    {
        $user = new User;
        $user->forceFill([
            'id' => 917,
            'name' => "{$this->role} cache index fixture",
            'email' => "{$this->role}-cache-index@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function nestedArray(array $payload, string $key): array
    {
        return is_array($payload[$key] ?? null) ? $payload[$key] : [];
    }

    private function firstString(mixed ...$values): string
    {
        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                $value = $this->valueSummary($value);
            }

            $stringValue = trim((string) $value);

            if ($stringValue !== '') {
                return $stringValue;
            }
        }

        return '';
    }

    private function valueSummary(mixed $value): string
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $nestedValue, string|int $nestedKey): string => is_int($nestedKey)
                    ? $this->valueSummary($nestedValue)
                    : Str::headline((string) $nestedKey).': '.$this->valueSummary($nestedValue))
                ->filter()
                ->implode(' / ');
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        return (string) $value;
    }
}
