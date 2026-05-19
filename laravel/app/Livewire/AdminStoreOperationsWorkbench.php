<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Policies\Modernization\Domain\DomainPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class AdminStoreOperationsWorkbench extends Component
{
    public string $storeView = '';

    public string $storeId = '';

    public string $query = '';

    public string $status = '';

    public string $type = '';

    public string $section = 'stores';

    public string $role = 'catalog';

    public function setSection(string $section): void
    {
        if (! in_array($section, $this->allowedSections(), true)) {
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
        $this->section = 'stores';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $this->storeId = $this->allowedStoreId();
        $this->storeView = $this->allowedStoreView();
        $this->status = $this->allowedStatus();
        $this->type = $this->allowedType();
        $this->section = $this->allowedSection();
        $this->role = $this->allowedRole();

        $canViewStoreOperations = $this->canViewStoreOperations($policy);
        $filters = $this->snapshotFilters();
        $storeSnapshot = $canViewStoreOperations ? $queryService->snapshot('store_scope', $filters) : null;
        $backupSnapshot = $canViewStoreOperations ? $queryService->snapshot('backup', $filters) : null;
        $systemSnapshot = $canViewStoreOperations ? $queryService->snapshot('system_info', $filters) : null;
        $templateSnapshot = $canViewStoreOperations ? $queryService->snapshot('email_template', $filters) : null;
        $rewriteSnapshot = $canViewStoreOperations ? $queryService->snapshot('url_rewrite', $filters) : null;
        $sitemapSnapshot = $canViewStoreOperations ? $queryService->snapshot('sitemap', $filters) : null;

        $storeRows = $this->storeRows($this->snapshotRows($storeSnapshot));
        $backupRows = $this->backupRows($this->snapshotRows($backupSnapshot));
        $systemRows = $this->systemRows($this->snapshotRows($systemSnapshot));
        $templateRows = $this->templateRows($this->snapshotRows($templateSnapshot));
        $rewriteRows = $this->rewriteRows($this->snapshotRows($rewriteSnapshot));
        $sitemapRows = $this->sitemapRows($this->snapshotRows($sitemapSnapshot));
        $problemRows = array_values(array_filter([...$storeRows, ...$backupRows, ...$systemRows, ...$templateRows, ...$rewriteRows, ...$sitemapRows], fn (array $row): bool => (bool) $row['is_problem']));

        return view('livewire.admin-store-operations-workbench', [
            'storeFeature' => $catalog->get('store_scope'),
            'backupFeature' => $catalog->get('backup'),
            'systemFeature' => $catalog->get('system_info'),
            'templateFeature' => $catalog->get('email_template'),
            'rewriteFeature' => $catalog->get('url_rewrite'),
            'sitemapFeature' => $catalog->get('sitemap'),
            'featureIds' => array_values(array_unique([
                'AD-017',
                ...$catalog->get('store_scope')->featureIds,
                ...$catalog->get('backup')->featureIds,
                ...$catalog->get('system_info')->featureIds,
                ...$catalog->get('email_template')->featureIds,
                ...$catalog->get('url_rewrite')->featureIds,
                ...$catalog->get('sitemap')->featureIds,
            ])),
            'canViewStoreOperations' => $canViewStoreOperations,
            'storeRows' => $storeRows,
            'backupRows' => $backupRows,
            'systemRows' => $systemRows,
            'templateRows' => $templateRows,
            'rewriteRows' => $rewriteRows,
            'sitemapRows' => $sitemapRows,
            'problemRows' => $problemRows,
            'currentRows' => $this->filterRows($this->currentRows($storeRows, $backupRows, $systemRows, $templateRows, $rewriteRows, $sitemapRows, $problemRows)),
            'activeFilters' => $this->activeFilters(),
            'snapshotStoreView' => $storeSnapshot['store_view'] ?? null,
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
    private function storeRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $canUseDefault = (bool) ($payload['can_use_default'] ?? true);

                return $this->baseRow($row, [
                    'domain' => 'store_scope',
                    'type' => 'store_view',
                    'type_label' => 'Store view',
                    'status' => $canUseDefault ? 'active' : 'inherited',
                    'status_label' => $canUseDefault ? 'Active' : 'Inherited',
                    'title' => $this->firstString($payload['store_name'] ?? null, $payload['store_code'] ?? null, 'Store view'),
                    'subtitle' => $this->firstString($payload['store_code'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'website' => $payload['website_code'] ?? null,
                        'store_group' => $payload['store_group_code'] ?? null,
                        'locale' => $payload['locale'] ?? null,
                        'currency' => $payload['currency'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'base_url' => $payload['base_url'] ?? null,
                        'secure_base_url' => $payload['secure_base_url'] ?? null,
                        'home_page' => $payload['cms_home_page'] ?? null,
                        'no_route' => $payload['cms_no_route'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->firstString($payload['default_title'] ?? null),
                    'is_problem' => false,
                    'action_label' => 'Open Store View',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function backupRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'completed');
                $verification = $this->nestedArray($payload, 'verification');

                return $this->baseRow($row, [
                    'domain' => 'backup',
                    'type' => 'backup',
                    'type_label' => 'Backup',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['label'] ?? null, $payload['file'] ?? null, 'Backup'),
                    'subtitle' => $this->firstString($payload['file'] ?? null, $payload['path'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'kind' => $payload['kind'] ?? null,
                        'includes_media' => $payload['includes_media'] ?? null,
                        'rollback_allowed' => $payload['rollback_allowed'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'created_at' => $payload['created_at'] ?? null,
                        'checksum' => $verification['checksum'] ?? null,
                        'failure' => $payload['failure_reason'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->firstString($payload['size'] ?? null, $payload['retention_state'] ?? null),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || $status === 'failed',
                    'action_label' => 'Inspect Backup',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function systemRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'healthy');

                return $this->baseRow($row, [
                    'domain' => 'system_info',
                    'type' => 'system_info',
                    'type_label' => 'System info',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['label'] ?? null, $payload['area'] ?? null, 'System info'),
                    'subtitle' => $this->firstString($payload['area'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'runtime' => $payload['runtime'] ?? null,
                        'database' => $payload['database'] ?? null,
                        'cache' => $payload['cache'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== [] && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'extensions' => $payload['extensions'] ?? [],
                        'warning' => $payload['warning'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== [] && $value !== '')),
                    'metric' => $this->firstString($payload['visibility'] ?? null),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || $status === 'warning',
                    'action_label' => 'Inspect System Info',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function templateRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'default');

                return $this->baseRow($row, [
                    'domain' => 'email_template',
                    'type' => 'email_template',
                    'type_label' => 'Email template',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['label'] ?? null, $payload['code'] ?? null, 'Email template'),
                    'subtitle' => $this->firstString($payload['code'] ?? null, $payload['subject'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'area' => $payload['area'] ?? null,
                        'locale' => $payload['locale'] ?? null,
                        'used_by' => $payload['used_by'] ?? [],
                    ], fn (mixed $value): bool => $value !== null && $value !== [] && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'subject' => $payload['subject'] ?? null,
                        'sender' => $payload['sender_identity'] ?? null,
                        'variables' => $payload['variables'] ?? [],
                        'problem' => $payload['problem'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== [] && $value !== '')),
                    'metric' => 'custom template: '.$this->valueSummary((bool) ($payload['template_actual'] ?? false)),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || $status === 'invalid',
                    'action_label' => 'Preview Template',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function rewriteRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $redirectType = (int) ($payload['redirect_type'] ?? 0);
                $metadata = $this->nestedArray($payload, 'metadata');
                $status = $redirectType > 0 ? 'redirect' : 'canonical';

                return $this->baseRow($row, [
                    'domain' => 'url_rewrite',
                    'type' => 'url_rewrite',
                    'type_label' => 'URL rewrite',
                    'status' => $status,
                    'status_label' => $redirectType > 0 ? $redirectType.' redirect' : 'Canonical',
                    'title' => $this->firstString($payload['request_path'] ?? null, 'URL rewrite'),
                    'subtitle' => $this->firstString($payload['target_path'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'entity_type' => $payload['entity_type'] ?? null,
                        'source' => $metadata['source'] ?? null,
                        'preserve_query' => $metadata['preserve_query'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->firstString($payload['canonical_url'] ?? null, $metadata['reason'] ?? null),
                    'metric' => $redirectType > 0 ? $redirectType.' redirect' : 'canonical',
                    'is_problem' => false,
                    'action_label' => 'Inspect URL Rewrite',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function sitemapRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $isFresh = (bool) ($payload['is_fresh'] ?? true);
                $type = (string) ($payload['type'] ?? 'sitemap');

                return $this->baseRow($row, [
                    'domain' => 'sitemap',
                    'type' => $type,
                    'type_label' => $type === 'rss' ? 'RSS feed' : 'Sitemap',
                    'status' => $isFresh ? 'fresh' : 'stale',
                    'status_label' => $isFresh ? 'Fresh' : 'Stale',
                    'title' => $this->firstString($payload['file'] ?? null, $payload['url'] ?? null, 'Sitemap'),
                    'subtitle' => $this->firstString($payload['url'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'generated_at' => $payload['generated_at'] ?? null,
                        'included_features' => $payload['included_features'] ?? [],
                        'rss' => $payload['rss'] ?? [],
                    ], fn (mixed $value): bool => $value !== null && $value !== [] && $value !== '')),
                    'detail' => $this->firstString($payload['stale_reason'] ?? null),
                    'metric' => $this->firstString($payload['url_count'] ?? null, $payload['items_count'] ?? null, '0').' items',
                    'is_problem' => ! $isFresh,
                    'action_label' => $type === 'rss' ? 'Inspect Feed' : 'Inspect Sitemap',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function baseRow(array $row, array $values): array
    {
        return [
            'entity_id' => (int) ($row['entity_id'] ?? 0),
            'store_id' => (int) ($row['store_id'] ?? 0),
            'store_view' => (string) ($row['store_view'] ?? ''),
            ...$values,
        ];
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
                if ($status === 'problem' && ! $row['is_problem']) {
                    return false;
                }

                if ($status !== '' && $status !== 'problem' && $row['status'] !== $status) {
                    return false;
                }

                if ($type !== '' && $row['type'] !== $type && $row['domain'] !== $type) {
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
            $row['domain'] ?? '',
            $row['type_label'] ?? '',
            $row['status_label'] ?? '',
            $row['title'] ?? '',
            $row['subtitle'] ?? '',
            $row['summary'] ?? '',
            $row['detail'] ?? '',
            $row['metric'] ?? '',
            $row['store_view'] ?? '',
        ])));
    }

    /**
     * @param  list<array<string, mixed>>  $storeRows
     * @param  list<array<string, mixed>>  $backupRows
     * @param  list<array<string, mixed>>  $systemRows
     * @param  list<array<string, mixed>>  $templateRows
     * @param  list<array<string, mixed>>  $rewriteRows
     * @param  list<array<string, mixed>>  $sitemapRows
     * @param  list<array<string, mixed>>  $problemRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $storeRows, array $backupRows, array $systemRows, array $templateRows, array $rewriteRows, array $sitemapRows, array $problemRows): array
    {
        return match ($this->allowedSection()) {
            'backups' => $backupRows,
            'system' => $systemRows,
            'templates' => $templateRows,
            'rewrites' => $rewriteRows,
            'sitemaps' => $sitemapRows,
            'problems' => $problemRows,
            default => $storeRows,
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

    /**
     * @return list<string>
     */
    private function allowedSections(): array
    {
        return ['stores', 'backups', 'system', 'templates', 'rewrites', 'sitemaps', 'problems'];
    }

    private function allowedSection(): string
    {
        return in_array($this->section, $this->allowedSections(), true) ? $this->section : 'stores';
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
        return in_array($this->status, ['', 'active', 'inherited', 'completed', 'scheduled', 'failed', 'healthy', 'warning', 'customized', 'default', 'invalid', 'canonical', 'redirect', 'fresh', 'stale', 'problem'], true)
            ? $this->status
            : '';
    }

    private function allowedType(): string
    {
        return in_array($this->type, ['', 'store_view', 'backup', 'system_info', 'email_template', 'url_rewrite', 'sitemap', 'rss'], true) ? $this->type : '';
    }

    private function allowedRole(): string
    {
        return in_array($this->role, ['catalog', 'read-only', 'full', 'denied'], true) ? $this->role : 'catalog';
    }

    private function canViewStoreOperations(DomainPolicy $policy): bool
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
            'id' => 919,
            'name' => "{$this->role} admin store operations fixture",
            'email' => "{$this->role}-admin-store-operations@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }
}
