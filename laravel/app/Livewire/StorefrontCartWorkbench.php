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

class StorefrontCartWorkbench extends Component
{
    public string $storeView = '';

    public string $storeId = '';

    public string $query = '';

    public string $status = '';

    public string $type = '';

    public string $section = 'quotes';

    public string $role = 'customer';

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
        $this->section = 'quotes';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $this->storeId = $this->allowedStoreId();
        $this->storeView = $this->allowedStoreView();
        $this->status = $this->allowedStatus();
        $this->type = $this->allowedType();
        $this->section = $this->allowedSection();
        $this->role = $this->allowedRole();

        $canViewCart = $this->canViewCart($policy);
        $filters = $this->snapshotFilters();
        $quoteSnapshot = $canViewCart ? $queryService->snapshot('quote', $filters) : null;
        $itemSnapshot = $canViewCart ? $queryService->snapshot('cart_item', $filters) : null;
        $totalSnapshot = $canViewCart ? $queryService->snapshot('cart_total', $filters) : null;
        $shippingSnapshot = $canViewCart ? $queryService->snapshot('shipping_rate', $filters) : null;

        $quoteRows = $this->quoteRows($this->snapshotRows($quoteSnapshot));
        $itemRows = $this->itemRows($this->snapshotRows($itemSnapshot));
        $totalRows = $this->totalRows($this->snapshotRows($totalSnapshot));
        $shippingRows = $this->shippingRows($this->snapshotRows($shippingSnapshot));
        $problemRows = array_values(array_filter([...$quoteRows, ...$itemRows, ...$totalRows, ...$shippingRows], fn (array $row): bool => (bool) $row['is_problem']));

        return view('livewire.storefront-cart-workbench', [
            'quoteFeature' => $catalog->get('quote'),
            'itemFeature' => $catalog->get('cart_item'),
            'totalFeature' => $catalog->get('cart_total'),
            'shippingFeature' => $catalog->get('shipping_rate'),
            'featureIds' => array_values(array_unique([
                'SF-007',
                ...$catalog->get('quote')->featureIds,
                ...$catalog->get('cart_item')->featureIds,
                ...$catalog->get('cart_total')->featureIds,
                ...$catalog->get('shipping_rate')->featureIds,
            ])),
            'canViewCart' => $canViewCart,
            'quoteRows' => $quoteRows,
            'itemRows' => $itemRows,
            'totalRows' => $totalRows,
            'shippingRows' => $shippingRows,
            'problemRows' => $problemRows,
            'currentRows' => $this->filterRows($this->currentRows($quoteRows, $itemRows, $totalRows, $shippingRows, $problemRows)),
            'activeFilters' => $this->activeFilters(),
            'snapshotStoreView' => $quoteSnapshot['store_view'] ?? null,
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
    private function quoteRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'active');

                return $this->baseRow($row, [
                    'domain' => 'quote',
                    'type' => 'quote',
                    'type_label' => 'Quote',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['title'] ?? null, $payload['quote_code'] ?? null, 'Quote'),
                    'subtitle' => $this->firstString($payload['customer_label'] ?? null, $payload['checkout_mode'] ?? null, $payload['quote_code'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'items' => $payload['items_count'] ?? null,
                        'coupon' => $payload['coupon_code'] ?? null,
                        'checkout_mode' => $payload['checkout_mode'] ?? null,
                        'persistent' => $payload['persistent_cart'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'created_at' => $payload['created_at'] ?? null,
                        'updated_at' => $payload['updated_at'] ?? null,
                        'expires_at' => $payload['expires_at'] ?? null,
                        'merge_state' => $payload['merge_state'] ?? null,
                        'problem_type' => $payload['problem_type'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->money($payload['grand_total'] ?? null, $payload['currency'] ?? 'USD'),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || in_array($status, ['expired', 'stale'], true),
                    'action_label' => 'Inspect Quote',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function itemRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'valid');

                return $this->baseRow($row, [
                    'domain' => 'cart_item',
                    'type' => 'item',
                    'type_label' => 'Cart item',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['name'] ?? null, $payload['sku'] ?? null, 'Cart item'),
                    'subtitle' => $this->firstString($payload['sku'] ?? null, $payload['product_type'] ?? null, $payload['quote_code'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'qty' => $payload['qty'] ?? null,
                        'unit_price' => $this->money($payload['unit_price'] ?? null, $payload['currency'] ?? 'USD'),
                        'row_total' => $this->money($payload['row_total'] ?? null, $payload['currency'] ?? 'USD'),
                        'stock' => $payload['stock_state'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'selected_options' => $payload['selected_options'] ?? null,
                        'qty_rules' => $payload['qty_rules'] ?? null,
                        'stock' => $payload['stock'] ?? null,
                        'message' => $payload['message'] ?? null,
                        'problem_type' => $payload['problem_type'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->firstString($payload['quote_code'] ?? null),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || in_array($status, ['invalid_quantity', 'out_of_stock', 'backordered'], true),
                    'action_label' => 'Inspect Item',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function totalRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'collected');
                $currency = $payload['currency'] ?? 'USD';

                return $this->baseRow($row, [
                    'domain' => 'cart_total',
                    'type' => 'total',
                    'type_label' => 'Cart total',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['title'] ?? null, $payload['quote_code'] ?? null, 'Cart total'),
                    'subtitle' => $this->firstString($payload['collector_sequence'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'subtotal' => $this->money($payload['subtotal'] ?? null, $currency),
                        'discount' => $this->money($payload['discount'] ?? null, $currency),
                        'tax' => $this->money($payload['tax'] ?? null, $currency),
                        'shipping' => $this->money($payload['shipping'] ?? null, $currency),
                        'grand_total' => $this->money($payload['grand_total'] ?? null, $currency),
                        'currency' => $currency,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'coupon_code' => $payload['coupon_code'] ?? null,
                        'tax_mode' => $payload['tax_mode'] ?? null,
                        'shipping_method' => $payload['shipping_method'] ?? null,
                        'problem_type' => $payload['problem_type'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->money($payload['grand_total'] ?? null, $currency),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || $status === 'stale',
                    'action_label' => 'Inspect Totals',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function shippingRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'available');

                return $this->baseRow($row, [
                    'domain' => 'shipping_rate',
                    'type' => 'shipping',
                    'type_label' => 'Shipping rate',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['title'] ?? null, $payload['carrier_title'] ?? null, 'Shipping rate'),
                    'subtitle' => $this->firstString($payload['carrier_code'] ?? null, $payload['method_code'] ?? null, $payload['quote_code'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'amount' => $this->money($payload['amount'] ?? null, $payload['currency'] ?? 'USD'),
                        'destination' => $payload['destination'] ?? null,
                        'free_shipping' => $payload['free_shipping'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'request' => $payload['request'] ?? null,
                        'message' => $payload['message'] ?? null,
                        'problem_type' => $payload['problem_type'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->firstString($payload['quote_code'] ?? null),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || $status === 'unavailable',
                    'action_label' => 'Inspect Shipping',
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
     * @param  list<array<string, mixed>>  $quoteRows
     * @param  list<array<string, mixed>>  $itemRows
     * @param  list<array<string, mixed>>  $totalRows
     * @param  list<array<string, mixed>>  $shippingRows
     * @param  list<array<string, mixed>>  $problemRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $quoteRows, array $itemRows, array $totalRows, array $shippingRows, array $problemRows): array
    {
        return match ($this->allowedSection()) {
            'items' => $itemRows,
            'totals' => $totalRows,
            'shipping' => $shippingRows,
            'problems' => $problemRows,
            default => $quoteRows,
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

    private function money(mixed $amount, mixed $currency): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        return Number::currency((float) $amount, in: (string) $currency, locale: 'en_US');
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
        return ['quotes', 'items', 'totals', 'shipping', 'problems'];
    }

    private function allowedSection(): string
    {
        return in_array($this->section, $this->allowedSections(), true) ? $this->section : 'quotes';
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
        return in_array($this->status, ['', 'active', 'customer', 'expired', 'valid', 'invalid_quantity', 'low_stock', 'backordered', 'out_of_stock', 'collected', 'free_shipping', 'stale', 'available', 'unavailable', 'virtual_only', 'problem'], true)
            ? $this->status
            : '';
    }

    private function allowedType(): string
    {
        return in_array($this->type, ['', 'quote', 'item', 'total', 'shipping'], true) ? $this->type : '';
    }

    private function allowedRole(): string
    {
        return in_array($this->role, ['customer', 'read-only', 'full', 'denied'], true) ? $this->role : 'customer';
    }

    private function canViewCart(DomainPolicy $policy): bool
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
            'id' => 940,
            'name' => "{$this->role} storefront cart fixture",
            'email' => "{$this->role}-storefront-cart@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }
}
