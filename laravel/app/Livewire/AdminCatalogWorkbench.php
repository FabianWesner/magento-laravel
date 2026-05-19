<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Policies\Modernization\Domain\DomainPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class AdminCatalogWorkbench extends Component
{
    public string $storeView = 'default';

    public string $storeId = '';

    public string $query = '';

    public string $status = '';

    public string $type = '';

    public string $section = 'products';

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
        $this->storeView = 'default';
        $this->storeId = '';
        $this->query = '';
        $this->status = '';
        $this->type = '';
        $this->section = 'products';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $this->storeId = $this->allowedStoreId();
        $this->storeView = $this->allowedStoreView();
        $this->status = $this->allowedStatus();
        $this->type = $this->allowedType();
        $this->section = $this->allowedSection();
        $this->role = $this->allowedRole();

        $canViewCatalog = $this->canViewCatalog($policy);
        $filters = $this->snapshotFilters();
        $productSnapshot = $canViewCatalog ? $queryService->snapshot('product', $filters) : null;
        $categorySnapshot = $canViewCatalog ? $queryService->snapshot('category', $filters) : null;
        $mediaSnapshot = $canViewCatalog ? $queryService->snapshot('product_media', $filters) : null;
        $downloadableSnapshot = $canViewCatalog ? $queryService->snapshot('downloadable', $filters) : null;

        $productRows = $this->decorateProducts($this->snapshotRows($productSnapshot));
        $categoryRows = $this->decorateCategories($this->snapshotRows($categorySnapshot));
        $mediaRows = $this->decorateMediaRows($this->snapshotRows($mediaSnapshot));
        $downloadRows = $this->decorateDownloadRows($this->snapshotRows($downloadableSnapshot));
        $attributeRows = $this->attributeRows($productRows, $categoryRows);
        $allRows = [...$productRows, ...$categoryRows, ...$attributeRows, ...$mediaRows, ...$downloadRows];
        $problemRows = array_values(array_filter($allRows, fn (array $row): bool => (bool) $row['is_problem']));

        return view('livewire.admin-catalog-workbench', [
            'productFeature' => $catalog->get('product'),
            'categoryFeature' => $catalog->get('category'),
            'mediaFeature' => $catalog->get('product_media'),
            'downloadableFeature' => $catalog->get('downloadable'),
            'featureIds' => array_values(array_unique([
                'AD-002',
                'AD-003',
                'AD-004',
                ...$catalog->get('product')->featureIds,
                ...$catalog->get('category')->featureIds,
                ...$catalog->get('product_media')->featureIds,
                ...$catalog->get('downloadable')->featureIds,
            ])),
            'canViewCatalog' => $canViewCatalog,
            'productRows' => $productRows,
            'categoryRows' => $categoryRows,
            'attributeRows' => $attributeRows,
            'mediaRows' => $mediaRows,
            'downloadRows' => $downloadRows,
            'problemRows' => $problemRows,
            'currentRows' => $this->filterRows($this->currentRows($productRows, $categoryRows, $attributeRows, $mediaRows, $downloadRows, $problemRows)),
            'activeFilters' => $this->activeFilters(),
            'snapshotStoreView' => $productSnapshot['store_view'] ?? null,
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
    private function decorateProducts(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $stock = $this->nestedArray($payload, 'stock');
                $type = (string) ($payload['type'] ?? 'product');
                $status = Str::lower((string) ($payload['status'] ?? 'enabled'));
                $stockStatus = ((bool) ($stock['is_in_stock'] ?? false)) ? 'in_stock' : 'out_of_stock';
                $isProblem = $status !== 'enabled' || $stockStatus === 'out_of_stock';

                return $this->baseRow($row, [
                    'domain' => 'product',
                    'section' => 'products',
                    'type' => $type,
                    'type_label' => Str::headline($type),
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'stock_status' => $stockStatus,
                    'title' => $this->firstString($payload['name'] ?? null, $payload['sku'] ?? null, 'Product'),
                    'subtitle' => $this->firstString($payload['sku'] ?? null),
                    'summary' => $this->firstString($payload['short_description'] ?? null, $payload['description'] ?? null),
                    'detail' => $this->valueSummary(array_filter([
                        'attribute_set' => $payload['attribute_set'] ?? null,
                        'visibility' => $payload['visibility'] ?? null,
                        'url_key' => $payload['url_key'] ?? null,
                        'stock_qty' => $stock['qty'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->money($payload['final_price'] ?? $payload['price'] ?? null, (string) ($payload['currency'] ?? '')),
                    'is_problem' => $isProblem,
                    'action_label' => 'Inspect Product',
                    'payload' => $payload,
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decorateCategories(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $isActive = (bool) ($payload['is_active'] ?? false);
                $productIds = $payload['product_ids'] ?? [];

                return $this->baseRow($row, [
                    'domain' => 'category',
                    'section' => 'categories',
                    'type' => 'category',
                    'type_label' => 'Category',
                    'status' => $isActive ? 'active' : 'inactive',
                    'status_label' => $isActive ? 'Active' : 'Inactive',
                    'stock_status' => '',
                    'title' => $this->firstString($payload['name'] ?? null, 'Category'),
                    'subtitle' => $this->firstString($payload['url_key'] ?? null),
                    'summary' => 'Admin category tree row with scoped URL key and product assignment diagnostics.',
                    'detail' => $this->valueSummary([
                        'url_key' => $payload['url_key'] ?? '',
                        'product_ids' => $productIds,
                    ]),
                    'metric' => count(is_array($productIds) ? $productIds : []).' products',
                    'is_problem' => ! $isActive,
                    'action_label' => 'Inspect Category',
                    'payload' => $payload,
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decorateMediaRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $gallery = is_array($payload['gallery'] ?? null) ? $payload['gallery'] : [];
                $missingMedia = (bool) ($payload['missing_media'] ?? false);

                return $this->baseRow($row, [
                    'domain' => 'product_media',
                    'section' => 'media',
                    'type' => 'media',
                    'type_label' => 'Media',
                    'status' => $missingMedia ? 'missing_media' : 'ready',
                    'status_label' => $missingMedia ? 'Missing Media' : 'Ready',
                    'stock_status' => '',
                    'title' => $this->firstString($payload['sku'] ?? null, 'Product media'),
                    'subtitle' => $this->firstString($payload['base_image'] ?? null),
                    'summary' => 'Media gallery, base image, small image, thumbnail, disabled flags, and missing file diagnostics.',
                    'detail' => $this->valueSummary(array_filter([
                        'small_image' => $payload['small_image'] ?? null,
                        'thumbnail' => $payload['thumbnail'] ?? null,
                        'gallery' => collect($gallery)->pluck('label')->filter()->values()->all(),
                        'missing_files' => $payload['missing_files'] ?? [],
                    ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== [])),
                    'metric' => count($gallery).' gallery rows',
                    'is_problem' => $missingMedia,
                    'action_label' => 'Inspect Media',
                    'payload' => $payload,
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decorateDownloadRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $requiresLogin = (bool) ($payload['requires_login'] ?? false);

                return $this->baseRow($row, [
                    'domain' => 'downloadable',
                    'section' => 'downloads',
                    'type' => 'downloadable',
                    'type_label' => 'Downloadable',
                    'status' => $requiresLogin ? 'requires_login' : 'shareable',
                    'status_label' => $requiresLogin ? 'Requires Login' : 'Shareable',
                    'stock_status' => '',
                    'title' => $this->firstString($payload['sku'] ?? null, 'Downloadable product'),
                    'subtitle' => $this->firstString($payload['file'] ?? null),
                    'summary' => 'Downloadable link, sample file, shareability, group permission, and related product diagnostics.',
                    'detail' => $this->valueSummary(array_filter([
                        'sample_file' => $payload['sample_file'] ?? null,
                        'permission' => $payload['permission'] ?? null,
                        'groups' => $payload['customer_group_permissions'] ?? [],
                        'related_products' => $payload['related_product_skus'] ?? [],
                    ], fn (mixed $value): bool => $value !== null && $value !== '' && $value !== [])),
                    'metric' => $requiresLogin ? 'login required' : 'shareable',
                    'is_problem' => false,
                    'action_label' => 'Inspect Download',
                    'payload' => $payload,
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $productRows
     * @param  list<array<string, mixed>>  $categoryRows
     * @return list<array<string, mixed>>
     */
    private function attributeRows(array $productRows, array $categoryRows): array
    {
        $rows = [];

        foreach ($productRows as $productRow) {
            $payload = $productRow['payload'];
            $rows[] = $this->attributeRow($productRow, 'attribute_set', $productRow['title'].' attribute set', $this->valueSummary([
                'attribute_set' => $payload['attribute_set'] ?? '',
                'url_key' => $payload['url_key'] ?? '',
                'visibility' => $payload['visibility'] ?? '',
                'status' => $payload['status'] ?? '',
            ]));

            foreach ($payload['custom_options'] ?? [] as $option) {
                if (! is_array($option)) {
                    continue;
                }

                $rows[] = $this->attributeRow($productRow, 'custom_option', $productRow['title'].' custom option', $this->valueSummary($option));
            }

            foreach ($payload['configurable_options'] ?? [] as $option) {
                if (! is_array($option)) {
                    continue;
                }

                $rows[] = $this->attributeRow($productRow, 'configurable_attribute', $productRow['title'].' configurable attribute', $this->valueSummary($option));
            }
        }

        foreach ($categoryRows as $categoryRow) {
            $payload = $categoryRow['payload'];
            $rows[] = $this->attributeRow($categoryRow, 'category_attribute', $categoryRow['title'].' category attributes', $this->valueSummary([
                'url_key' => $payload['url_key'] ?? '',
                'is_active' => $payload['is_active'] ?? false,
                'product_ids' => $payload['product_ids'] ?? [],
            ]));
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $sourceRow
     * @return array<string, mixed>
     */
    private function attributeRow(array $sourceRow, string $type, string $title, string $summary): array
    {
        return [
            'domain' => 'attribute',
            'section' => 'attributes',
            'type' => $type,
            'type_label' => Str::headline($type),
            'status' => 'tracked',
            'status_label' => 'Tracked',
            'stock_status' => '',
            'entity_id' => (int) $sourceRow['entity_id'],
            'store_id' => (int) $sourceRow['store_id'],
            'store_view' => (string) $sourceRow['store_view'],
            'title' => $title,
            'subtitle' => $sourceRow['subtitle'],
            'summary' => $summary,
            'detail' => 'Read-only EAV signal derived from deterministic domain facts.',
            'metric' => 'EAV signal',
            'is_problem' => false,
            'action_label' => 'Inspect Attributes',
            'payload' => $sourceRow['payload'],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function baseRow(array $row, array $attributes): array
    {
        return [
            ...$attributes,
            'entity_id' => (int) ($row['entity_id'] ?? 0),
            'store_id' => (int) ($row['store_id'] ?? 0),
            'store_view' => (string) ($row['store_view'] ?? ''),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $productRows
     * @param  list<array<string, mixed>>  $categoryRows
     * @param  list<array<string, mixed>>  $attributeRows
     * @param  list<array<string, mixed>>  $mediaRows
     * @param  list<array<string, mixed>>  $downloadRows
     * @param  list<array<string, mixed>>  $problemRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $productRows, array $categoryRows, array $attributeRows, array $mediaRows, array $downloadRows, array $problemRows): array
    {
        return match ($this->allowedSection()) {
            'categories' => $categoryRows,
            'attributes' => $attributeRows,
            'media' => $mediaRows,
            'downloads' => $downloadRows,
            'problems' => $problemRows,
            default => $productRows,
        };
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
                if ($status !== '') {
                    $matchesStatus = $row['status'] === $status
                        || $row['stock_status'] === $status
                        || ($status === 'problem' && (bool) $row['is_problem']);

                    if (! $matchesStatus) {
                        return false;
                    }
                }

                if ($type !== '' && $row['type'] !== $type) {
                    return false;
                }

                if ($query === '') {
                    return true;
                }

                $haystack = Str::lower(implode(' ', [
                    $row['title'],
                    $row['subtitle'],
                    $row['summary'],
                    $row['detail'],
                    $row['metric'],
                    $row['type_label'],
                    $row['status_label'],
                ]));

                return Str::contains($haystack, $query);
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function activeFilters(): array
    {
        return array_filter([
            'store' => $this->allowedStoreView(),
            'store_id' => $this->allowedStoreId(),
            'status' => $this->allowedStatus(),
            'type' => $this->allowedType(),
            'search' => trim($this->query),
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

    /**
     * @param  mixed  ...$values
     */
    private function firstString(...$values): string
    {
        foreach ($values as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }

            if (is_int($value) || is_float($value)) {
                return (string) $value;
            }
        }

        return '';
    }

    private function money(mixed $amount, string $currency): string
    {
        if (! is_numeric($amount)) {
            return '';
        }

        return trim($currency.' '.number_format((float) $amount, 2));
    }

    private function valueSummary(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value) || is_string($value)) {
            return (string) $value;
        }

        if (! is_array($value)) {
            return '';
        }

        $parts = [];

        foreach ($value as $key => $item) {
            $summary = $this->valueSummary($item);

            if ($summary === '') {
                continue;
            }

            $parts[] = is_string($key) ? "{$key}: {$summary}" : $summary;
        }

        return implode(' / ', $parts);
    }

    private function allowedStoreId(): string
    {
        return in_array($this->storeId, ['', '9001', '9002'], true) ? $this->storeId : '';
    }

    private function allowedStoreView(): string
    {
        return in_array($this->storeView, ['', 'default', 'de'], true) ? $this->storeView : 'default';
    }

    private function allowedStatus(): string
    {
        return in_array($this->status, ['', 'enabled', 'disabled', 'active', 'inactive', 'in_stock', 'out_of_stock', 'ready', 'missing_media', 'requires_login', 'shareable', 'tracked', 'problem'], true) ? $this->status : '';
    }

    private function allowedType(): string
    {
        return in_array($this->type, ['', 'simple', 'configurable', 'grouped', 'bundle', 'virtual', 'downloadable', 'category', 'media', 'attribute_set', 'custom_option', 'configurable_attribute', 'category_attribute'], true) ? $this->type : '';
    }

    private function allowedSection(): string
    {
        return in_array($this->section, $this->allowedSections(), true) ? $this->section : 'products';
    }

    private function allowedRole(): string
    {
        return in_array($this->role, ['catalog', 'read-only', 'full', 'denied'], true) ? $this->role : 'catalog';
    }

    /**
     * @return list<string>
     */
    private function allowedSections(): array
    {
        return ['products', 'categories', 'attributes', 'media', 'downloads', 'problems'];
    }

    private function canViewCatalog(DomainPolicy $policy): bool
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
            'name' => "{$this->allowedRole()} admin catalog fixture",
            'email' => "{$this->allowedRole()}-admin-catalog@example.test",
        ]);
        $user->setAttribute('role', $this->allowedRole());
        $user->exists = true;

        return $user;
    }
}
