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

class CustomerCommerceWorkbench extends Component
{
    public string $storeView = '';

    public string $storeId = '';

    public string $customerId = '';

    public string $productSku = '';

    public string $status = '';

    public string $section = 'wishlist';

    public string $role = 'customer';

    public function setSection(string $section): void
    {
        if (! in_array($section, ['wishlist', 'compare', 'reviews', 'tags'], true)) {
            return;
        }

        $this->section = $section;
    }

    public function clearFilters(): void
    {
        $this->storeView = '';
        $this->storeId = '';
        $this->customerId = '';
        $this->productSku = '';
        $this->status = '';
        $this->section = 'wishlist';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $this->storeId = $this->allowedStoreId();
        $this->storeView = $this->allowedStoreView();
        $this->status = $this->allowedStatus();
        $this->section = $this->allowedSection();

        $canViewCommerce = $this->canViewCommerce($policy);
        $wishlistSnapshot = $canViewCommerce ? $queryService->snapshot('wishlist', $this->snapshotFilters()) : null;
        $compareSnapshot = $canViewCommerce ? $queryService->snapshot('compare', $this->snapshotFilters()) : null;
        $reviewSnapshot = $canViewCommerce ? $queryService->snapshot('review', $this->snapshotFilters()) : null;
        $tagSnapshot = $canViewCommerce ? $queryService->snapshot('tag', $this->snapshotFilters()) : null;

        $wishlistRows = $this->filterRows($this->decorateRows($this->snapshotRows($wishlistSnapshot), 'wishlist'));
        $compareRows = $this->filterRows($this->decorateRows($this->snapshotRows($compareSnapshot), 'compare'));
        $reviewRows = $this->filterRows($this->decorateRows($this->snapshotRows($reviewSnapshot), 'review'));
        $tagRows = $this->filterRows($this->decorateRows($this->snapshotRows($tagSnapshot), 'tag'));

        return view('livewire.customer-commerce-workbench', [
            'wishlistFeature' => $catalog->get('wishlist'),
            'compareFeature' => $catalog->get('compare'),
            'reviewFeature' => $catalog->get('review'),
            'tagFeature' => $catalog->get('tag'),
            'featureIds' => array_values(array_unique([
                ...$catalog->get('wishlist')->featureIds,
                ...$catalog->get('compare')->featureIds,
                ...$catalog->get('review')->featureIds,
                ...$catalog->get('tag')->featureIds,
            ])),
            'canViewCommerce' => $canViewCommerce,
            'wishlistRows' => $wishlistRows,
            'compareRows' => $compareRows,
            'reviewRows' => $reviewRows,
            'tagRows' => $tagRows,
            'currentRows' => $this->currentRows($wishlistRows, $compareRows, $reviewRows, $tagRows),
            'activeFilters' => $this->activeFilters(),
            'snapshotStoreView' => $wishlistSnapshot['store_view'] ?? null,
            'customerOptions' => $this->customerOptions([$wishlistRows, $compareRows, $reviewRows, $tagRows]),
            'productOptions' => $this->productOptions([$wishlistRows, $compareRows, $reviewRows, $tagRows]),
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
        $items = $this->items($payload);
        $visibleSkus = array_values(array_map('strval', $payload['visible_product_skus'] ?? $payload['related_product_skus'] ?? []));
        $status = (string) ($payload['status'] ?? $payload['state'] ?? ($payload['is_active'] ?? true ? 'active' : 'disabled'));
        $ratings = $payload['ratings'] ?? [];

        return [
            'type' => $type,
            'entity_id' => (int) ($row['entity_id'] ?? 0),
            'store_id' => (int) ($row['store_id'] ?? 0),
            'store_view' => (string) ($row['store_view'] ?? ''),
            'customer_id' => (string) ($payload['customer_id'] ?? ''),
            'customer_name' => (string) ($payload['customer_name'] ?? $payload['customer_email'] ?? $this->customerLabel($payload['customer_id'] ?? null)),
            'product_sku' => (string) ($payload['product_sku'] ?? $payload['sku'] ?? ($items[0]['sku'] ?? ($visibleSkus[0] ?? ''))),
            'product_name' => (string) ($payload['product_name'] ?? $payload['name'] ?? $this->headlineSku((string) ($payload['product_sku'] ?? ($items[0]['sku'] ?? ($visibleSkus[0] ?? 'Product'))))),
            'visible_skus' => $visibleSkus,
            'status' => $status,
            'status_label' => Str::headline($status),
            'items' => $items,
            'item_count' => (int) ($payload['items_count'] ?? count($items)),
            'item_summary' => $this->itemSummary($items, $visibleSkus),
            'is_shared' => $status === 'shared' || (string) ($payload['visibility'] ?? '') === 'shared_link',
            'share_code' => (string) ($payload['sharing_code'] ?? $payload['share_code'] ?? ''),
            'attribute_summary' => $this->attributeSummary($payload),
            'title' => (string) ($payload['title'] ?? $payload['name'] ?? Str::headline($type)),
            'body' => (string) ($payload['detail'] ?? $payload['message'] ?? $payload['description'] ?? ''),
            'rating_summary' => (string) ($payload['rating_summary'] ?? $payload['average_rating'] ?? $this->ratingSummary($ratings)),
            'rating_breakdown' => $this->ratingBreakdown($ratings),
            'uses_count' => (int) ($payload['uses_count'] ?? $payload['product_count'] ?? 0),
            'created_at' => (string) ($payload['created_at'] ?? $payload['shared_at'] ?? ''),
            'denied_reason' => (string) ($payload['denied_reason'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, string>>
     */
    private function items(array $payload): array
    {
        return collect($payload['items'] ?? [])
            ->map(fn (array $item): array => [
                'sku' => (string) ($item['sku'] ?? ''),
                'name' => (string) ($item['name'] ?? $this->headlineSku((string) ($item['sku'] ?? 'Product'))),
                'qty' => (string) ($item['qty'] ?? '1'),
                'description' => (string) ($item['description'] ?? ''),
                'price' => $this->priceLabel($item),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function priceLabel(array $item): string
    {
        if (! array_key_exists('price', $item)) {
            return '';
        }

        return Number::currency((float) $item['price'], in: (string) ($item['currency'] ?? 'USD'), locale: 'en_US');
    }

    private function headlineSku(string $sku): string
    {
        return Str::headline(str_replace('-', ' ', $sku));
    }

    private function customerLabel(mixed $customerId): string
    {
        if ($customerId === null || $customerId === '') {
            return 'Guest session';
        }

        return 'Customer '.$customerId;
    }

    /**
     * @param  list<array<string, string>>  $items
     * @param  list<string>  $visibleSkus
     */
    private function itemSummary(array $items, array $visibleSkus): string
    {
        if ($items !== []) {
            return collect($items)
                ->map(fn (array $item): string => trim($item['name'].' '.$item['sku']))
                ->implode(', ');
        }

        if ($visibleSkus !== []) {
            return implode(', ', $visibleSkus);
        }

        return 'No items';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function attributeSummary(array $payload): string
    {
        $attributes = collect($payload['attributes'] ?? $payload['compared_attributes'] ?? [])
            ->map(function (mixed $value, string|int $key): string {
                if (is_array($value) && array_key_exists('values', $value)) {
                    return (string) ($value['label'] ?? $value['code'] ?? $key).': '.$this->valueSummary($value['values']);
                }

                if (is_array($value)) {
                    return (string) ($value['label'] ?? $value['code'] ?? $key).': '.(string) ($value['value'] ?? $this->valueSummary($value));
                }

                return Str::headline((string) $key).': '.(string) $value;
            })
            ->filter()
            ->values();

        return $attributes->isEmpty() ? 'No compared attributes' : $attributes->implode(' / ');
    }

    private function valueSummary(mixed $value): string
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $nestedValue, string|int $nestedKey): string => (string) $nestedKey.' '.(string) $nestedValue)
                ->implode(', ');
        }

        return (string) $value;
    }

    /**
     * @param  array<string, mixed>  $ratings
     */
    private function ratingSummary(array $ratings): string
    {
        if ($ratings === []) {
            return 'No rating';
        }

        return number_format((float) collect($ratings)->avg(), 1).' / 5';
    }

    /**
     * @param  array<string, mixed>  $ratings
     */
    private function ratingBreakdown(array $ratings): string
    {
        if ($ratings === []) {
            return 'No rating breakdown';
        }

        return collect($ratings)
            ->map(fn (mixed $rating, string $label): string => Str::headline($label).': '.(string) $rating)
            ->implode(' / ');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function filterRows(array $rows): array
    {
        $customerId = trim($this->customerId);
        $productSku = trim(Str::lower($this->productSku));
        $status = $this->allowedStatus();

        return collect($rows)
            ->filter(function (array $row) use ($customerId, $productSku, $status): bool {
                if ($customerId !== '' && (string) ($row['customer_id'] ?? '') !== $customerId) {
                    return false;
                }

                if ($status !== '' && (string) ($row['status'] ?? '') !== $status) {
                    return false;
                }

                if ($productSku === '') {
                    return true;
                }

                $haystack = Str::lower(implode(' ', [
                    $row['product_sku'] ?? '',
                    $row['product_name'] ?? '',
                    $row['item_summary'] ?? '',
                    $row['title'] ?? '',
                    $row['body'] ?? '',
                ]));

                return Str::contains($haystack, $productSku);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $wishlistRows
     * @param  list<array<string, mixed>>  $compareRows
     * @param  list<array<string, mixed>>  $reviewRows
     * @param  list<array<string, mixed>>  $tagRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $wishlistRows, array $compareRows, array $reviewRows, array $tagRows): array
    {
        return match ($this->section) {
            'compare' => $compareRows,
            'reviews' => $reviewRows,
            'tags' => $tagRows,
            default => $wishlistRows,
        };
    }

    /**
     * @param  list<list<array<string, mixed>>>  $rowGroups
     * @return array<string, string>
     */
    private function customerOptions(array $rowGroups): array
    {
        $options = collect($rowGroups)
            ->flatten(1)
            ->filter(fn (array $row): bool => (string) ($row['customer_id'] ?? '') !== '')
            ->reduce(function (array $options, array $row): array {
                $customerId = (string) $row['customer_id'];
                $label = (string) ($row['customer_name'] ?? $customerId);

                if (! isset($options[$customerId]) || str_starts_with($options[$customerId], 'Customer ')) {
                    $options[$customerId] = $label;
                }

                return $options;
            }, []);

        ksort($options);

        return $options;
    }

    /**
     * @param  list<list<array<string, mixed>>>  $rowGroups
     * @return list<string>
     */
    private function productOptions(array $rowGroups): array
    {
        return collect($rowGroups)
            ->flatten(1)
            ->flatMap(function (array $row): array {
                $skus = [$row['product_sku'] ?? ''];

                foreach ($row['items'] ?? [] as $item) {
                    $skus[] = $item['sku'] ?? '';
                }

                foreach ($row['visible_skus'] ?? [] as $sku) {
                    $skus[] = $sku;
                }

                return $skus;
            })
            ->filter(fn (mixed $sku): bool => is_string($sku) && $sku !== '')
            ->unique()
            ->sort()
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
            'customer' => trim($this->customerId),
            'product' => trim($this->productSku),
            'status' => $this->allowedStatus(),
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
        return in_array($this->status, ['', 'active', 'shared', 'empty_private', 'empty_guest', 'pending', 'approved', 'disabled'], true) ? $this->status : '';
    }

    private function allowedSection(): string
    {
        return in_array($this->section, ['wishlist', 'compare', 'reviews', 'tags'], true) ? $this->section : 'wishlist';
    }

    private function canViewCommerce(DomainPolicy $policy): bool
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
            'id' => 907,
            'name' => "{$this->role} commerce fixture",
            'email' => "{$this->role}-commerce@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }
}
