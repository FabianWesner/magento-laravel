<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Policies\Modernization\Domain\DomainPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class CmsSeoWorkbench extends Component
{
    public string $storeView = '';

    public string $storeId = '';

    public string $query = '';

    public string $contentState = '';

    public string $freshness = '';

    public string $section = 'pages';

    public string $role = 'catalog';

    public function setSection(string $section): void
    {
        if (! in_array($section, ['pages', 'blocks', 'widgets', 'seo'], true)) {
            return;
        }

        $this->section = $section;
    }

    public function clearFilters(): void
    {
        $this->storeView = '';
        $this->storeId = '';
        $this->query = '';
        $this->contentState = '';
        $this->freshness = '';
        $this->section = 'pages';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $this->storeId = $this->allowedStoreId();
        $this->storeView = $this->allowedStoreView();
        $this->contentState = $this->allowedContentState();
        $this->freshness = $this->allowedFreshness();
        $this->section = $this->allowedSection();

        $canViewContent = $this->canViewContent($policy);
        $filters = $this->snapshotFilters();

        $pageSnapshot = $canViewContent ? $queryService->snapshot('cms_page', $filters) : null;
        $blockSnapshot = $canViewContent ? $queryService->snapshot('cms_block', $filters) : null;
        $widgetSnapshot = $canViewContent ? $queryService->snapshot('widget', $filters) : null;
        $sitemapSnapshot = $canViewContent ? $queryService->snapshot('sitemap', $filters) : null;
        $rewriteSnapshot = $canViewContent ? $queryService->snapshot('url_rewrite', $filters) : null;
        $scopeSnapshot = $canViewContent ? $queryService->snapshot('store_scope', $filters) : null;

        $pageRows = $this->filterRows($this->decorateRows($this->snapshotRows($pageSnapshot), 'page'));
        $blockRows = $this->filterRows($this->decorateRows($this->snapshotRows($blockSnapshot), 'block'));
        $widgetRows = $this->filterRows($this->decorateRows($this->snapshotRows($widgetSnapshot), 'widget'));
        $seoRows = $this->filterRows([
            ...$this->decorateRows($this->snapshotRows($sitemapSnapshot), 'sitemap'),
            ...$this->decorateRows($this->snapshotRows($rewriteSnapshot), 'url_rewrite'),
            ...$this->decorateRows($this->snapshotRows($scopeSnapshot), 'store_scope'),
        ]);

        return view('livewire.cms-seo-workbench', [
            'pageFeature' => $catalog->get('cms_page'),
            'blockFeature' => $catalog->get('cms_block'),
            'widgetFeature' => $catalog->get('widget'),
            'sitemapFeature' => $catalog->get('sitemap'),
            'rewriteFeature' => $catalog->get('url_rewrite'),
            'featureIds' => array_values(array_unique([
                ...$catalog->get('cms_page')->featureIds,
                ...$catalog->get('cms_block')->featureIds,
                ...$catalog->get('widget')->featureIds,
                ...$catalog->get('sitemap')->featureIds,
                ...$catalog->get('url_rewrite')->featureIds,
                ...$catalog->get('store_scope')->featureIds,
            ])),
            'canViewContent' => $canViewContent,
            'pageRows' => $pageRows,
            'blockRows' => $blockRows,
            'widgetRows' => $widgetRows,
            'seoRows' => $seoRows,
            'currentRows' => $this->currentRows($pageRows, $blockRows, $widgetRows, $seoRows),
            'selectedPage' => $pageRows[0] ?? [],
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
    private function decorateRows(array $rows, string $type): array
    {
        return collect($rows)
            ->map(fn (array $row): array => $this->decorateRow($row, $type))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function decorateRow(array $row, string $type): array
    {
        $payload = $row['payload'] ?? [];
        $redirect = $payload['redirect'] ?? null;
        $redirectType = (int) ($payload['redirect_type'] ?? ($payload['redirect']['type'] ?? 0));
        $hasRedirect = is_array($redirect) || $redirectType > 0;
        $isNoRoute = (bool) ($payload['is_no_route'] ?? false) || in_array($payload['identifier'] ?? $payload['request_path'] ?? '', ['no-route', '404'], true);
        $isActive = $this->isActive($payload, $type);
        $isStale = $this->isStale($payload);
        $title = $this->title($payload, $type);
        $identifier = $this->identifier($payload, $type);
        $url = (string) ($payload['url'] ?? $payload['request_path'] ?? $payload['path'] ?? '');
        $canonicalUrl = (string) ($payload['canonical_url'] ?? $payload['canonical'] ?? $url);

        return [
            'type' => $type,
            'type_label' => $this->typeLabel($type),
            'entity_id' => (int) ($row['entity_id'] ?? 0),
            'store_id' => (int) ($row['store_id'] ?? 0),
            'store_view' => (string) ($row['store_view'] ?? ''),
            'title' => $title,
            'identifier' => $identifier,
            'url' => $url,
            'canonical_url' => $canonicalUrl,
            'meta_title' => (string) ($payload['meta_title'] ?? $title),
            'meta_description' => (string) ($payload['meta_description'] ?? $payload['description'] ?? ''),
            'status' => $this->status($payload, $isActive, $isNoRoute, $hasRedirect),
            'status_label' => Str::headline($this->status($payload, $isActive, $isNoRoute, $hasRedirect)),
            'is_active' => $isActive,
            'is_no_route' => $isNoRoute,
            'has_redirect' => $hasRedirect,
            'redirect_summary' => $this->redirectSummary($payload, $hasRedirect),
            'is_stale' => $isStale,
            'freshness_label' => $isStale ? 'Stale' : 'Fresh',
            'summary' => $this->summary($payload, $type),
            'detail' => $this->detail($payload, $type),
            'rss_url' => $this->rssUrl($payload),
            'item_count' => (int) ($payload['item_count'] ?? $payload['url_count'] ?? $payload['rows_count'] ?? $payload['items_count'] ?? 0),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function filterRows(array $rows): array
    {
        $query = Str::lower(trim(Str::substr($this->query, 0, 128)));
        $contentState = $this->allowedContentState();
        $freshness = $this->allowedFreshness();

        return collect($rows)
            ->filter(function (array $row) use ($contentState, $freshness, $query): bool {
                if ($contentState === 'active' && ! $row['is_active']) {
                    return false;
                }

                if ($contentState === 'disabled' && $row['is_active']) {
                    return false;
                }

                if ($contentState === 'no_route' && ! $row['is_no_route']) {
                    return false;
                }

                if ($contentState === 'redirect' && ! $row['has_redirect']) {
                    return false;
                }

                if ($freshness === 'fresh' && $row['is_stale']) {
                    return false;
                }

                if ($freshness === 'stale' && ! $row['is_stale']) {
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
            $row['type_label'] ?? '',
            $row['title'] ?? '',
            $row['identifier'] ?? '',
            $row['url'] ?? '',
            $row['canonical_url'] ?? '',
            $row['meta_title'] ?? '',
            $row['meta_description'] ?? '',
            $row['status_label'] ?? '',
            $row['redirect_summary'] ?? '',
            $row['summary'] ?? '',
            $row['detail'] ?? '',
            $row['rss_url'] ?? '',
        ])));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function title(array $payload, string $type): string
    {
        return (string) ($payload['title']
            ?? $payload['name']
            ?? $payload['label']
            ?? $payload['request_path']
            ?? $payload['file']
            ?? $payload['filename']
            ?? Str::headline($type));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function identifier(array $payload, string $type): string
    {
        return (string) ($payload['identifier']
            ?? $payload['code']
            ?? $payload['instance_code']
            ?? $payload['request_path']
            ?? $payload['file']
            ?? $payload['filename']
            ?? Str::slug($this->title($payload, $type)));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isActive(array $payload, string $type): bool
    {
        if (array_key_exists('is_active', $payload)) {
            return (bool) $payload['is_active'];
        }

        if (array_key_exists('active', $payload)) {
            return (bool) $payload['active'];
        }

        $status = Str::lower((string) ($payload['status'] ?? $payload['state'] ?? 'active'));

        if (in_array($status, ['disabled', 'inactive', 'archived'], true)) {
            return false;
        }

        return $type !== 'store_scope' || $status !== 'closed';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isStale(array $payload): bool
    {
        if (array_key_exists('is_fresh', $payload)) {
            return ! (bool) $payload['is_fresh'];
        }

        return (bool) ($payload['is_stale'] ?? $payload['is_index_stale'] ?? $payload['needs_regeneration'] ?? false);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function rssUrl(array $payload): string
    {
        $rss = $payload['rss'] ?? [];

        return (string) ($payload['rss_url'] ?? (is_array($rss) ? ($rss['feed_url'] ?? '') : ''));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function status(array $payload, bool $isActive, bool $isNoRoute, bool $hasRedirect): string
    {
        if ($isNoRoute) {
            return 'no_route';
        }

        if ($hasRedirect) {
            return 'redirect';
        }

        return (string) ($payload['status'] ?? $payload['state'] ?? ($isActive ? 'active' : 'disabled'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function redirectSummary(array $payload, bool $hasRedirect): string
    {
        if (! $hasRedirect) {
            return 'No redirect';
        }

        $redirect = is_array($payload['redirect'] ?? null) ? $payload['redirect'] : [];

        return implode(' / ', array_filter([
            (string) ($payload['redirect_type'] ?? $redirect['type'] ?? ''),
            (string) ($payload['target_path'] ?? $redirect['target'] ?? ''),
            (string) ($payload['redirect_reason'] ?? $redirect['reason'] ?? ''),
        ]));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function summary(array $payload, string $type): string
    {
        return match ($type) {
            'page' => (string) ($payload['content_heading'] ?? $payload['heading'] ?? $payload['layout'] ?? 'CMS page'),
            'block' => (string) ($payload['content_summary'] ?? $payload['content_heading'] ?? $payload['block_type'] ?? 'CMS block'),
            'widget' => (string) ($payload['widget_type'] ?? $payload['type'] ?? $payload['template'] ?? 'Widget output'),
            'sitemap' => (string) ($payload['generated_at'] ?? $payload['path'] ?? 'Sitemap output'),
            'url_rewrite' => (string) ($payload['target_path'] ?? $payload['entity_type'] ?? 'URL rewrite'),
            'store_scope' => $this->valueSummary($payload['fallback_chain'] ?? $payload['config_scope'] ?? $payload['base_url'] ?? 'Store scope'),
            default => $this->valueSummary($payload),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function detail(array $payload, string $type): string
    {
        return match ($type) {
            'page' => $this->valueSummary($payload['content_blocks'] ?? $payload['layout_updates'] ?? $payload['content'] ?? ''),
            'block' => $this->valueSummary($payload['used_in_pages'] ?? $payload['media_refs'] ?? $payload['cache_tags'] ?? ''),
            'widget' => $this->valueSummary($payload['assigned_pages'] ?? $payload['parameters'] ?? $payload['layout_handles'] ?? ''),
            'sitemap' => $this->valueSummary($payload['included_features'] ?? $payload['stale_reason'] ?? $payload['rss'] ?? ''),
            'url_rewrite' => $this->valueSummary($payload['entity_type'] ?? $payload['category_path'] ?? $payload['product_sku'] ?? ''),
            'store_scope' => $this->valueSummary($payload['locale'] ?? $payload['config_path'] ?? ''),
            default => '',
        };
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

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'page' => 'CMS page',
            'block' => 'CMS block',
            'widget' => 'Widget',
            'sitemap' => 'Sitemap/RSS',
            'url_rewrite' => 'URL rewrite',
            'store_scope' => 'Store scope',
            default => Str::headline($type),
        };
    }

    /**
     * @param  list<array<string, mixed>>  $pageRows
     * @param  list<array<string, mixed>>  $blockRows
     * @param  list<array<string, mixed>>  $widgetRows
     * @param  list<array<string, mixed>>  $seoRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $pageRows, array $blockRows, array $widgetRows, array $seoRows): array
    {
        return match ($this->allowedSection()) {
            'blocks' => $blockRows,
            'widgets' => $widgetRows,
            'seo' => $seoRows,
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
            'content' => $this->allowedContentState(),
            'freshness' => $this->allowedFreshness(),
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

    private function allowedContentState(): string
    {
        return in_array($this->contentState, ['', 'active', 'disabled', 'no_route', 'redirect'], true) ? $this->contentState : '';
    }

    private function allowedFreshness(): string
    {
        return in_array($this->freshness, ['', 'fresh', 'stale'], true) ? $this->freshness : '';
    }

    private function allowedSection(): string
    {
        return in_array($this->section, ['pages', 'blocks', 'widgets', 'seo'], true) ? $this->section : 'pages';
    }

    private function canViewContent(DomainPolicy $policy): bool
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
            'id' => 913,
            'name' => "{$this->role} cms seo fixture",
            'email' => "{$this->role}-cms-seo@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }
}
