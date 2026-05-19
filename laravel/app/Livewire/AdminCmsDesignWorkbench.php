<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Policies\Modernization\Domain\DomainPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class AdminCmsDesignWorkbench extends Component
{
    public string $storeView = '';

    public string $storeId = '';

    public string $query = '';

    public string $status = '';

    public string $type = '';

    public string $section = 'pages';

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
        $this->section = 'pages';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $this->storeId = $this->allowedStoreId();
        $this->storeView = $this->allowedStoreView();
        $this->status = $this->allowedStatus();
        $this->type = $this->allowedType();
        $this->section = $this->allowedSection();
        $this->role = $this->allowedRole();

        $canViewCmsDesign = $this->canViewCmsDesign($policy);
        $filters = $this->snapshotFilters();
        $pageSnapshot = $canViewCmsDesign ? $queryService->snapshot('cms_page', $filters) : null;
        $blockSnapshot = $canViewCmsDesign ? $queryService->snapshot('cms_block', $filters) : null;
        $widgetSnapshot = $canViewCmsDesign ? $queryService->snapshot('widget', $filters) : null;
        $rewriteSnapshot = $canViewCmsDesign ? $queryService->snapshot('url_rewrite', $filters) : null;
        $scopeSnapshot = $canViewCmsDesign ? $queryService->snapshot('store_scope', $filters) : null;
        $cacheSnapshot = $canViewCmsDesign ? $queryService->snapshot('cache', $filters) : null;

        $pageRows = $this->decoratePageRows($this->snapshotRows($pageSnapshot));
        $blockRows = $this->decorateBlockRows($this->snapshotRows($blockSnapshot));
        $widgetRows = $this->decorateWidgetRows($this->snapshotRows($widgetSnapshot));
        $rewriteRows = $this->decorateRewriteRows($this->snapshotRows($rewriteSnapshot));
        $designRows = [
            ...$this->decorateDesignRows($this->snapshotRows($scopeSnapshot)),
            ...$this->decorateCacheRows($this->snapshotRows($cacheSnapshot)),
        ];
        $allRows = [...$pageRows, ...$blockRows, ...$widgetRows, ...$designRows, ...$rewriteRows];
        $problemRows = array_values(array_filter($allRows, fn (array $row): bool => (bool) $row['is_problem']));

        return view('livewire.admin-cms-design-workbench', [
            'pageFeature' => $catalog->get('cms_page'),
            'blockFeature' => $catalog->get('cms_block'),
            'widgetFeature' => $catalog->get('widget'),
            'rewriteFeature' => $catalog->get('url_rewrite'),
            'scopeFeature' => $catalog->get('store_scope'),
            'cacheFeature' => $catalog->get('cache'),
            'featureIds' => array_values(array_unique([
                'AD-009',
                ...$catalog->get('cms_page')->featureIds,
                ...$catalog->get('cms_block')->featureIds,
                ...$catalog->get('widget')->featureIds,
                ...$catalog->get('url_rewrite')->featureIds,
                ...$catalog->get('store_scope')->featureIds,
                ...$catalog->get('cache')->featureIds,
            ])),
            'canViewCmsDesign' => $canViewCmsDesign,
            'pageRows' => $pageRows,
            'blockRows' => $blockRows,
            'widgetRows' => $widgetRows,
            'designRows' => $designRows,
            'rewriteRows' => $rewriteRows,
            'problemRows' => $problemRows,
            'currentRows' => $this->filterRows($this->currentRows($pageRows, $blockRows, $widgetRows, $designRows, $rewriteRows, $problemRows)),
            'activeFilters' => $this->activeFilters(),
            'snapshotStoreView' => $pageSnapshot['store_view'] ?? null,
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
    private function decoratePageRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $isActive = (bool) ($payload['is_active'] ?? ((string) ($payload['status'] ?? '') === 'active'));
                $isNoRoute = (bool) ($payload['is_no_route'] ?? $payload['is_404'] ?? false);
                $hasRedirect = is_array($payload['redirect'] ?? null);
                $status = match (true) {
                    $isNoRoute => 'no_route',
                    $hasRedirect => 'redirect',
                    $isActive => 'active',
                    default => 'disabled',
                };

                return $this->baseRow($row, [
                    'domain' => 'cms_page',
                    'type' => 'cms_page',
                    'type_label' => 'CMS page',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['title'] ?? null, $payload['identifier'] ?? null, 'CMS page'),
                    'subtitle' => $this->firstString($payload['identifier'] ?? null, $payload['request_path'] ?? null),
                    'summary' => $this->firstString($payload['content_heading'] ?? null, $payload['content_preview'] ?? null),
                    'detail' => $this->valueSummary(array_filter([
                        'layout' => $payload['layout'] ?? null,
                        'blocks' => $payload['block_identifiers'] ?? [],
                        'widgets' => $payload['widget_instance_ids'] ?? [],
                        'meta_title' => $payload['meta_title'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== [] && $value !== '')),
                    'metric' => (string) ($payload['http_status'] ?? 200).' HTTP',
                    'is_problem' => in_array($status, ['disabled', 'no_route', 'redirect'], true),
                    'action_label' => 'Open Page Editor',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decorateBlockRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $isActive = (bool) ($payload['is_active'] ?? true);

                return $this->baseRow($row, [
                    'domain' => 'cms_block',
                    'type' => 'cms_block',
                    'type_label' => 'CMS block',
                    'status' => $isActive ? 'active' : 'disabled',
                    'status_label' => $isActive ? 'Active' : 'Disabled',
                    'title' => $this->firstString($payload['title'] ?? null, $payload['identifier'] ?? null, 'CMS block'),
                    'subtitle' => $this->firstString($payload['identifier'] ?? null),
                    'summary' => $this->firstString($payload['content_preview'] ?? null, 'Static block content'),
                    'detail' => $this->valueSummary(array_filter([
                        'used_on' => $payload['used_on'] ?? [],
                        'wysiwyg_media' => $payload['wysiwyg_media'] ?? [],
                        'cache_tags' => $payload['cache_tags'] ?? [],
                    ], fn (mixed $value): bool => $value !== [] && $value !== null && $value !== '')),
                    'metric' => count(is_array($payload['used_on'] ?? null) ? $payload['used_on'] : []).' page refs',
                    'is_problem' => ! $isActive,
                    'action_label' => 'Open Block Editor',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decorateWidgetRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $isActive = (bool) ($payload['is_active'] ?? false);
                $type = $this->normalizedType((string) ($payload['type'] ?? 'widget'));

                return $this->baseRow($row, [
                    'domain' => 'widget',
                    'type' => $type,
                    'type_label' => Str::headline(Str::of((string) ($payload['type'] ?? 'widget'))->replace('/', ' ')->toString()),
                    'status' => $isActive ? 'active' : 'disabled',
                    'status_label' => $isActive ? 'Active' : 'Disabled',
                    'title' => $this->firstString($payload['title'] ?? null, 'Widget instance'),
                    'subtitle' => $this->firstString($payload['template'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'page_groups' => $payload['page_groups'] ?? [],
                        'block_reference' => $payload['block_reference'] ?? null,
                    ], fn (mixed $value): bool => $value !== [] && $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'conditions' => $payload['conditions'] ?? [],
                        'cache_lifetime' => $payload['cache_lifetime'] ?? null,
                    ], fn (mixed $value): bool => $value !== [] && $value !== null && $value !== '')),
                    'metric' => 'sort '.$this->firstString($payload['sort_order'] ?? null, '0'),
                    'is_problem' => ! $isActive,
                    'action_label' => 'Open Widget Instance',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decorateRewriteRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $redirectType = (int) ($payload['redirect_type'] ?? 0);
                $status = $redirectType > 0 ? 'redirect' : 'canonical';

                return $this->baseRow($row, [
                    'domain' => 'url_rewrite',
                    'type' => 'url_rewrite',
                    'type_label' => 'URL rewrite',
                    'status' => $status,
                    'status_label' => $redirectType > 0 ? $redirectType.' redirect' : 'Canonical',
                    'title' => $this->firstString($payload['request_path'] ?? null, 'URL rewrite'),
                    'subtitle' => $this->firstString($payload['target_path'] ?? null),
                    'summary' => $this->firstString($payload['canonical_url'] ?? null, $payload['entity_type'] ?? null),
                    'detail' => $this->valueSummary($payload['metadata'] ?? []),
                    'metric' => $redirectType > 0 ? $redirectType.' redirect' : '0 redirect',
                    'is_problem' => $redirectType > 0,
                    'action_label' => 'Inspect Rewrite',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decorateDesignRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $inheritsDefault = (bool) ($payload['can_use_default'] ?? false);
                $status = $inheritsDefault ? 'inherited' : 'scoped';
                $config = is_array($payload['config'] ?? null) ? $payload['config'] : [];

                return $this->baseRow($row, [
                    'domain' => 'store_scope',
                    'type' => 'design_scope',
                    'type_label' => 'Design scope',
                    'status' => $status,
                    'status_label' => $inheritsDefault ? 'Inherits default' : 'Store scoped',
                    'title' => $this->firstString($payload['store_name'] ?? null, $payload['store_code'] ?? null, 'Store scope'),
                    'subtitle' => $this->valueSummary(array_filter([
                        'website' => $payload['website_code'] ?? null,
                        'store' => $payload['store_code'] ?? null,
                        'locale' => $payload['locale'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'summary' => $this->valueSummary(array_filter([
                        'base_url' => $payload['base_url'] ?? null,
                        'secure_base_url' => $payload['secure_base_url'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'home_page' => $payload['cms_home_page'] ?? null,
                        'no_route' => $payload['cms_no_route'] ?? null,
                        'wysiwyg' => $config['cms/wysiwyg/enabled'] ?? null,
                        'url_rewrites' => $config['web/seo/use_rewrites'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->valueSummary(array_filter([
                        'currency' => $payload['currency'] ?? null,
                        'title' => $payload['default_title'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'is_problem' => false,
                    'action_label' => 'Inspect Design Scope',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decorateCacheRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row): bool => in_array($row['payload']['cache_type'] ?? '', ['block_html', 'layout'], true))
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $isStale = (bool) ($payload['is_stale'] ?? false);
                $ui = is_array($payload['ui'] ?? null) ? $payload['ui'] : [];

                return $this->baseRow($row, [
                    'domain' => 'cache',
                    'type' => 'cache',
                    'type_label' => 'Cache dependency',
                    'status' => $isStale ? 'stale' : 'clean',
                    'status_label' => $isStale ? 'Stale' : 'Clean',
                    'title' => $this->firstString($payload['label'] ?? null, $payload['cache_type'] ?? null, 'Cache dependency'),
                    'subtitle' => $this->firstString($payload['cache_type'] ?? null),
                    'summary' => $this->firstString($payload['stale_reason'] ?? null, $ui['summary'] ?? null, 'Cache state'),
                    'detail' => $this->valueSummary(array_filter([
                        'cache_tags' => $payload['cache_tags'] ?? [],
                        'affected_paths' => $payload['affected_paths'] ?? [],
                    ], fn (mixed $value): bool => $value !== [] && $value !== null && $value !== '')),
                    'metric' => $this->firstString($payload['legacy_status'] ?? null, $payload['status'] ?? null),
                    'is_problem' => $isStale,
                    'action_label' => 'Refresh Cache Disabled',
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
            ...$values,
            'entity_id' => (int) ($row['entity_id'] ?? 0),
            'store_id' => (int) ($row['store_id'] ?? 0),
            'store_view' => (string) ($row['store_view'] ?? ''),
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
     * @param  list<array<string, mixed>>  $pageRows
     * @param  list<array<string, mixed>>  $blockRows
     * @param  list<array<string, mixed>>  $widgetRows
     * @param  list<array<string, mixed>>  $designRows
     * @param  list<array<string, mixed>>  $rewriteRows
     * @param  list<array<string, mixed>>  $problemRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $pageRows, array $blockRows, array $widgetRows, array $designRows, array $rewriteRows, array $problemRows): array
    {
        return match ($this->allowedSection()) {
            'blocks' => $blockRows,
            'widgets' => $widgetRows,
            'design' => $designRows,
            'rewrites' => $rewriteRows,
            'problems' => $problemRows,
            default => $pageRows,
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

    private function normalizedType(string $type): string
    {
        return Str::snake(str_replace(['/', '-'], '_', $type));
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

    private function firstString(mixed ...$values): string
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        return '';
    }

    /**
     * @return list<string>
     */
    private function allowedSections(): array
    {
        return ['pages', 'blocks', 'widgets', 'design', 'rewrites', 'problems'];
    }

    private function allowedSection(): string
    {
        return in_array($this->section, $this->allowedSections(), true) ? $this->section : 'pages';
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
        return in_array($this->status, ['', 'active', 'disabled', 'no_route', 'redirect', 'canonical', 'scoped', 'inherited', 'clean', 'stale', 'problem'], true)
            ? $this->status
            : '';
    }

    private function allowedType(): string
    {
        return in_array($this->type, ['', 'cms_page', 'cms_block', 'catalog_widget_new', 'cms_widget_block', 'url_rewrite', 'design_scope', 'cache'], true)
            ? $this->type
            : '';
    }

    private function allowedRole(): string
    {
        return in_array($this->role, ['catalog', 'read-only', 'full', 'denied'], true) ? $this->role : 'catalog';
    }

    private function canViewCmsDesign(DomainPolicy $policy): bool
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
            'name' => "{$this->role} admin cms design fixture",
            'email' => "{$this->role}-admin-cms-design@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }
}
