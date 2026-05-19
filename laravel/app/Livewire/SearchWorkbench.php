<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Policies\Modernization\Domain\DomainPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Component;

class SearchWorkbench extends Component
{
    public string $storeView = 'default';

    public string $storeId = '';

    public string $query = '';

    public string $queryType = '';

    public string $resultState = '';

    public string $redirectState = '';

    public string $indexState = '';

    public string $sort = 'snapshot';

    public string $sortDirection = 'asc';

    public string $limit = 'all';

    public string $section = 'results';

    public string $role = 'catalog';

    public function setSection(string $section): void
    {
        if (! in_array($section, ['results', 'terms', 'seo', 'index'], true)) {
            return;
        }

        $this->section = $section;
    }

    public function clearFilters(): void
    {
        $this->storeView = 'default';
        $this->storeId = '';
        $this->query = '';
        $this->queryType = '';
        $this->resultState = '';
        $this->redirectState = '';
        $this->indexState = '';
        $this->sort = 'snapshot';
        $this->sortDirection = 'asc';
        $this->limit = 'all';
        $this->section = 'results';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $this->section = $this->allowedSection();
        $canViewSearch = $this->canViewSearch($policy);
        $searchSnapshot = $canViewSearch ? $queryService->snapshot('search', $this->snapshotFilters()) : null;
        $productSnapshot = $canViewSearch ? $queryService->snapshot('product', $this->snapshotFilters()) : null;
        $allRows = $this->snapshotRows($searchSnapshot);
        $filteredRows = $this->filteredRows($allRows);
        $rows = $this->decorateSearchRows($filteredRows);
        $selectedSearch = $rows[0] ?? [];

        return view('livewire.search-workbench', [
            'feature' => $catalog->get('search'),
            'canViewSearch' => $canViewSearch,
            'rows' => $rows,
            'allRows' => $allRows,
            'selectedSearch' => $selectedSearch,
            'resultProducts' => $this->decorateProducts($this->resultProducts($this->snapshotRows($productSnapshot), $selectedSearch)),
            'activeFilters' => $this->activeFilters(),
            'snapshotStoreView' => $searchSnapshot['store_view'] ?? null,
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
    private function filteredRows(array $rows): array
    {
        $query = $this->normalizedQuery();
        $queryType = $this->allowedQueryType();
        $resultState = $this->allowedResultState();
        $redirectState = $this->allowedRedirectState();
        $indexState = $this->allowedIndexState();

        $filtered = collect($rows)
            ->filter(function (array $row) use ($indexState, $query, $queryType, $redirectState, $resultState): bool {
                $payload = $row['payload'] ?? [];
                $hasRedirect = is_array($payload['redirect'] ?? null);
                $resultsCount = (int) ($payload['results_count'] ?? 0);
                $isIndexStale = (bool) ($payload['is_index_stale'] ?? false);

                if ($queryType !== '' && ($payload['query_type'] ?? '') !== $queryType) {
                    return false;
                }

                if ($resultState === 'has_results' && $resultsCount <= 0) {
                    return false;
                }

                if ($resultState === 'no_results' && $resultsCount > 0) {
                    return false;
                }

                if ($redirectState === 'redirected' && ! $hasRedirect) {
                    return false;
                }

                if ($redirectState === 'not_redirected' && $hasRedirect) {
                    return false;
                }

                if ($indexState === 'stale' && ! $isIndexStale) {
                    return false;
                }

                if ($indexState === 'fresh' && $isIndexStale) {
                    return false;
                }

                if ($query === '') {
                    return true;
                }

                return Str::contains($this->searchHaystack($payload), Str::lower($query));
            });

        $sorter = match ($this->allowedSort()) {
            'results_count' => fn (array $row): int => (int) ($row['payload']['results_count'] ?? 0),
            'query_type' => fn (array $row): string => (string) ($row['payload']['query_type'] ?? ''),
            'query' => fn (array $row): string => (string) ($row['payload']['query'] ?? ''),
            default => fn (array $row): int => (int) ($row['entity_id'] ?? 0),
        };

        $sorted = $this->allowedSortDirection() === 'desc'
            ? $filtered->sortByDesc($sorter)
            : $filtered->sortBy($sorter);

        $rows = $sorted->values();

        if ($this->allowedLimit() !== 'all') {
            $rows = $rows->take((int) $this->allowedLimit());
        }

        return $rows->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function searchHaystack(array $payload): string
    {
        $redirect = $payload['redirect'] ?? [];

        return Str::lower(implode(' ', [
            $payload['query'] ?? '',
            $payload['query_type'] ?? '',
            implode(' ', $payload['synonyms'] ?? []),
            implode(' ', $payload['result_skus'] ?? []),
            $payload['canonical_url'] ?? '',
            $payload['rss_url'] ?? '',
            is_array($redirect) ? implode(' ', array_filter([
                $redirect['target'] ?? '',
                $redirect['reason'] ?? '',
                $redirect['type'] ?? '',
            ])) : '',
            $this->valueSummary($payload['filters'] ?? []),
        ]));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decorateSearchRows(array $rows): array
    {
        return collect($rows)
            ->map(fn (array $row): array => $this->decorateSearchRow($row))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function decorateSearchRow(array $row): array
    {
        $payload = $row['payload'] ?? [];
        $redirect = $payload['redirect'] ?? null;
        $resultSkus = array_values(array_map('strval', $payload['result_skus'] ?? []));
        $synonyms = array_values(array_map('strval', $payload['synonyms'] ?? []));
        $hasRedirect = is_array($redirect);

        return [
            'entity_id' => (int) ($row['entity_id'] ?? 0),
            'store_id' => (int) ($row['store_id'] ?? 0),
            'store_view' => (string) ($row['store_view'] ?? ''),
            'query_type' => (string) ($payload['query_type'] ?? 'quick'),
            'query_type_label' => Str::headline((string) ($payload['query_type'] ?? 'quick')),
            'query' => (string) ($payload['query'] ?? ''),
            'results_count' => (int) ($payload['results_count'] ?? 0),
            'result_skus' => $resultSkus,
            'result_sku_summary' => $resultSkus === [] ? 'No result SKUs' : implode(', ', $resultSkus),
            'synonyms' => $synonyms,
            'synonym_summary' => $synonyms === [] ? 'No synonyms' : implode(', ', $synonyms),
            'canonical_url' => (string) ($payload['canonical_url'] ?? ''),
            'rss_url' => (string) ($payload['rss_url'] ?? ''),
            'filters' => $payload['filters'] ?? [],
            'filter_summary' => $this->filterSummary($payload['filters'] ?? []),
            'has_redirect' => $hasRedirect,
            'redirect_target' => $hasRedirect ? (string) ($redirect['target'] ?? '') : '',
            'redirect_type' => $hasRedirect ? (string) ($redirect['type'] ?? '') : '',
            'redirect_reason' => $hasRedirect ? (string) ($redirect['reason'] ?? '') : '',
            'redirect_summary' => $hasRedirect
                ? implode(' / ', array_filter([(string) ($redirect['type'] ?? ''), (string) ($redirect['target'] ?? ''), (string) ($redirect['reason'] ?? '')]))
                : 'No redirect',
            'is_index_stale' => (bool) ($payload['is_index_stale'] ?? false),
            'index_label' => (bool) ($payload['is_index_stale'] ?? false) ? 'Index stale' : 'Index fresh',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $productRows
     * @param  array<string, mixed>  $selectedSearch
     * @return list<array<string, mixed>>
     */
    private function resultProducts(array $productRows, array $selectedSearch): array
    {
        $resultSkus = $selectedSearch['result_skus'] ?? [];
        $storeId = (int) ($selectedSearch['store_id'] ?? 0);
        $storeView = (string) ($selectedSearch['store_view'] ?? '');

        if ($resultSkus === []) {
            return [];
        }

        return collect($productRows)
            ->filter(function (array $row) use ($resultSkus, $storeId, $storeView): bool {
                if (! in_array((string) ($row['payload']['sku'] ?? ''), $resultSkus, true)) {
                    return false;
                }

                if ($storeId !== 0 && (int) ($row['store_id'] ?? 0) !== $storeId) {
                    return false;
                }

                if ($storeView !== '' && (string) ($row['store_view'] ?? '') !== $storeView) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $productRows
     * @return list<array<string, mixed>>
     */
    private function decorateProducts(array $productRows): array
    {
        return collect($productRows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $stock = $payload['stock'] ?? [];
                $rating = $payload['rating'] ?? [];
                $currency = (string) ($payload['currency'] ?? 'USD');

                return [
                    'entity_id' => (int) ($row['entity_id'] ?? 0),
                    'store_id' => (int) ($row['store_id'] ?? 0),
                    'store_view' => (string) ($row['store_view'] ?? ''),
                    'name' => (string) ($payload['name'] ?? 'Product'),
                    'sku' => (string) ($payload['sku'] ?? 'sku-unavailable'),
                    'type' => (string) ($payload['type'] ?? 'product'),
                    'type_label' => Str::headline((string) ($payload['type'] ?? 'product')),
                    'url' => (string) ($payload['url'] ?? ''),
                    'price' => Number::currency((float) ($payload['final_price'] ?? $payload['price'] ?? 0), in: $currency, locale: 'en_US'),
                    'stock_label' => (bool) ($stock['is_in_stock'] ?? false) ? 'In stock' : 'Out of stock',
                    'stock_qty' => (int) ($stock['qty'] ?? 0),
                    'visibility' => (string) ($payload['visibility'] ?? 'Catalog, Search'),
                    'rating' => (string) ($rating['summary'] ?? 'n/a'),
                    'reviews_count' => (int) ($rating['reviews_count'] ?? 0),
                    'short_description' => (string) ($payload['short_description'] ?? 'Magento product snapshot'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filterSummary(array $filters): string
    {
        if ($filters === []) {
            return 'No advanced filters';
        }

        return collect($filters)
            ->map(fn (mixed $value, string $key): string => Str::headline($key).': '.$this->valueSummary($value))
            ->implode(' / ');
    }

    private function valueSummary(mixed $value): string
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $nestedValue, string|int $nestedKey): string => Str::headline((string) $nestedKey).' '.$this->valueSummary($nestedValue))
                ->implode(', ');
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        return (string) $value;
    }

    /**
     * @return array<string, string>
     */
    private function activeFilters(): array
    {
        return array_filter([
            'store' => $this->allowedStoreView(),
            'store_id' => $this->allowedStoreId(),
            'query' => $this->normalizedQuery(),
            'type' => $this->allowedQueryType(),
            'results' => $this->allowedResultState(),
            'redirect' => $this->allowedRedirectState(),
            'index' => $this->allowedIndexState(),
            'limit' => $this->allowedLimit() === 'all' ? '' : $this->allowedLimit(),
        ], fn (string $value): bool => $value !== '');
    }

    private function normalizedQuery(): string
    {
        return trim(Str::substr($this->query, 0, 128));
    }

    private function allowedStoreId(): string
    {
        return in_array($this->storeId, ['', '9001', '9002'], true) ? $this->storeId : '';
    }

    private function allowedStoreView(): string
    {
        return in_array($this->storeView, ['', 'default', 'de'], true) ? $this->storeView : 'default';
    }

    private function allowedQueryType(): string
    {
        return in_array($this->queryType, ['', 'quick', 'advanced'], true) ? $this->queryType : '';
    }

    private function allowedResultState(): string
    {
        return in_array($this->resultState, ['', 'has_results', 'no_results'], true) ? $this->resultState : '';
    }

    private function allowedRedirectState(): string
    {
        return in_array($this->redirectState, ['', 'redirected', 'not_redirected'], true) ? $this->redirectState : '';
    }

    private function allowedIndexState(): string
    {
        return in_array($this->indexState, ['', 'fresh', 'stale'], true) ? $this->indexState : '';
    }

    private function allowedSort(): string
    {
        return in_array($this->sort, ['snapshot', 'query', 'results_count', 'query_type'], true) ? $this->sort : 'snapshot';
    }

    private function allowedSortDirection(): string
    {
        return $this->sortDirection === 'desc' ? 'desc' : 'asc';
    }

    private function allowedLimit(): string
    {
        return in_array($this->limit, ['all', '1', '2', '3'], true) ? $this->limit : 'all';
    }

    private function allowedSection(): string
    {
        return in_array($this->section, ['results', 'terms', 'seo', 'index'], true) ? $this->section : 'results';
    }

    private function canViewSearch(DomainPolicy $policy): bool
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
            'id' => 906,
            'name' => "{$this->role} search fixture",
            'email' => "{$this->role}-search@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }
}
