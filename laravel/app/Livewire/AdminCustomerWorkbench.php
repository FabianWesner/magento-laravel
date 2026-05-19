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

class AdminCustomerWorkbench extends Component
{
    public string $storeView = 'default';

    public string $storeId = '';

    public string $query = '';

    public string $status = '';

    public string $type = '';

    public string $section = 'customers';

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
        $this->storeView = 'default';
        $this->storeId = '';
        $this->query = '';
        $this->status = '';
        $this->type = '';
        $this->section = 'customers';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $this->storeId = $this->allowedStoreId();
        $this->storeView = $this->allowedStoreView();
        $this->status = $this->allowedStatus();
        $this->type = $this->allowedType();
        $this->section = $this->allowedSection();
        $this->role = $this->allowedRole();

        $canViewCustomers = $this->canViewCustomers($policy);
        $filters = $this->snapshotFilters();
        $customerSnapshot = $canViewCustomers ? $queryService->snapshot('customer', $filters) : null;
        $addressSnapshot = $canViewCustomers ? $queryService->snapshot('customer_address', $filters) : null;
        $wishlistSnapshot = $canViewCustomers ? $queryService->snapshot('wishlist', $filters) : null;
        $compareSnapshot = $canViewCustomers ? $queryService->snapshot('compare', $filters) : null;
        $reviewSnapshot = $canViewCustomers ? $queryService->snapshot('review', $filters) : null;
        $tagSnapshot = $canViewCustomers ? $queryService->snapshot('tag', $filters) : null;

        $customerRows = $this->decorateCustomers($this->snapshotRows($customerSnapshot));
        $addressRows = $this->decorateAddresses($this->snapshotRows($addressSnapshot));
        $activityRows = [
            ...$this->decorateActivityRows($this->snapshotRows($wishlistSnapshot), 'wishlist'),
            ...$this->decorateActivityRows($this->snapshotRows($compareSnapshot), 'compare'),
        ];
        $moderationRows = [
            ...$this->decorateModerationRows($this->snapshotRows($reviewSnapshot), 'review'),
            ...$this->decorateModerationRows($this->snapshotRows($tagSnapshot), 'tag'),
        ];
        $problemRows = array_values(array_filter([...$customerRows, ...$addressRows, ...$activityRows, ...$moderationRows], fn (array $row): bool => (bool) $row['is_problem']));

        return view('livewire.admin-customer-workbench', [
            'customerFeature' => $catalog->get('customer'),
            'addressFeature' => $catalog->get('customer_address'),
            'wishlistFeature' => $catalog->get('wishlist'),
            'compareFeature' => $catalog->get('compare'),
            'reviewFeature' => $catalog->get('review'),
            'tagFeature' => $catalog->get('tag'),
            'featureIds' => array_values(array_unique([
                'AD-007',
                ...$catalog->get('customer')->featureIds,
                ...$catalog->get('customer_address')->featureIds,
                ...$catalog->get('wishlist')->featureIds,
                ...$catalog->get('compare')->featureIds,
                ...$catalog->get('review')->featureIds,
                ...$catalog->get('tag')->featureIds,
            ])),
            'canViewCustomers' => $canViewCustomers,
            'customerRows' => $customerRows,
            'addressRows' => $addressRows,
            'activityRows' => $activityRows,
            'moderationRows' => $moderationRows,
            'problemRows' => $problemRows,
            'currentRows' => $this->filterRows($this->currentRows($customerRows, $addressRows, $activityRows, $moderationRows, $problemRows)),
            'activeFilters' => $this->activeFilters(),
            'snapshotStoreView' => $customerSnapshot['store_view'] ?? null,
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
    private function decorateCustomers(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $isActive = (bool) ($payload['is_active'] ?? false);
                $newsletterSubscribed = (bool) ($payload['newsletter_subscribed'] ?? false);

                return $this->baseRow($row, [
                    'type' => 'customer',
                    'type_label' => 'Customer',
                    'status' => $isActive ? 'active' : 'inactive',
                    'status_label' => $isActive ? 'Active' : 'Inactive',
                    'title' => $this->firstString($payload['full_name'] ?? null, $payload['email'] ?? null, 'Customer'),
                    'subtitle' => $this->firstString($payload['email'] ?? null),
                    'summary' => $this->valueSummary([
                        'group' => $payload['group'] ?? '',
                        'orders' => $payload['orders_count'] ?? 0,
                        'lifetime_value' => $this->money($payload['lifetime_value'] ?? null),
                        'newsletter' => $newsletterSubscribed ? 'subscribed' : 'not subscribed',
                    ]),
                    'detail' => $this->valueSummary([
                        'customer_id' => $payload['customer_id'] ?? '',
                        'created_at' => $payload['created_at'] ?? '',
                        'last_login_at' => $payload['last_login_at'] ?? '',
                        'default_billing' => $payload['default_billing_address_id'] ?? '',
                        'default_shipping' => $payload['default_shipping_address_id'] ?? '',
                    ]),
                    'metric' => $newsletterSubscribed ? 'subscribed' : 'unsubscribed',
                    'customer_id' => (string) ($payload['customer_id'] ?? ''),
                    'product_sku' => '',
                    'is_problem' => ! $isActive,
                    'action_label' => 'Inspect Customer',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decorateAddresses(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $isDefaultBilling = (bool) ($payload['is_default_billing'] ?? false);
                $isDefaultShipping = (bool) ($payload['is_default_shipping'] ?? false);

                return $this->baseRow($row, [
                    'type' => 'address',
                    'type_label' => 'Address',
                    'status' => $isDefaultBilling && $isDefaultShipping ? 'default_billing_shipping' : 'additional',
                    'status_label' => $isDefaultBilling && $isDefaultShipping ? 'Default Billing Shipping' : 'Additional',
                    'title' => trim($this->firstString($payload['firstname'] ?? null).' '.$this->firstString($payload['lastname'] ?? null)),
                    'subtitle' => $this->firstString($payload['company'] ?? null, $payload['city'] ?? null),
                    'summary' => $this->valueSummary($payload['formatted_lines'] ?? []),
                    'detail' => $this->valueSummary([
                        'customer_id' => $payload['customer_id'] ?? '',
                        'address_id' => $payload['address_id'] ?? '',
                        'city' => $payload['city'] ?? '',
                        'region' => $payload['region'] ?? '',
                        'postcode' => $payload['postcode'] ?? '',
                        'country' => $payload['country_id'] ?? '',
                        'telephone' => $payload['telephone'] ?? '',
                    ]),
                    'metric' => $this->firstString($payload['address_type'] ?? null),
                    'customer_id' => (string) ($payload['customer_id'] ?? ''),
                    'product_sku' => '',
                    'is_problem' => false,
                    'action_label' => 'Inspect Address',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decorateActivityRows(array $rows, string $type): array
    {
        return collect($rows)
            ->map(function (array $row) use ($type): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['state'] ?? $payload['status'] ?? 'active');
                $deniedReason = $this->firstString($payload['denied_reason'] ?? null);
                $visibleSkus = $payload['visible_product_skus'] ?? [];

                return $this->baseRow($row, [
                    'type' => $type,
                    'type_label' => Str::headline($type),
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $type === 'wishlist' ? 'Wishlist '.$this->firstString($payload['wishlist_id'] ?? null) : 'Compare '.$this->firstString($payload['compare_id'] ?? null),
                    'subtitle' => $this->firstString($payload['customer_email'] ?? null, $payload['customer_id'] ?? null, 'Guest session'),
                    'summary' => $this->valueSummary([
                        'items' => $payload['items_count'] ?? count($visibleSkus),
                        'visible_skus' => $visibleSkus,
                        'message' => $payload['message'] ?? '',
                        'sharing_code' => $payload['sharing_code'] ?? '',
                    ]),
                    'detail' => $this->valueSummary([
                        'customer_id' => $payload['customer_id'] ?? '',
                        'visibility' => $payload['visibility'] ?? '',
                        'attributes' => $this->attributeSummary($payload['attributes'] ?? []),
                        'denied_reason' => $deniedReason,
                    ]),
                    'metric' => $deniedReason !== '' ? $deniedReason : $status,
                    'customer_id' => (string) ($payload['customer_id'] ?? ''),
                    'product_sku' => $this->firstString($visibleSkus[0] ?? null),
                    'is_problem' => $deniedReason !== '' || str_contains($status, 'empty'),
                    'action_label' => $type === 'wishlist' ? 'Inspect Wishlist' : 'Inspect Compare',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decorateModerationRows(array $rows, string $type): array
    {
        return collect($rows)
            ->map(function (array $row) use ($type): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'pending');
                $ratings = $payload['ratings'] ?? [];

                return $this->baseRow($row, [
                    'type' => $type,
                    'type_label' => Str::headline($type),
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['title'] ?? null, $payload['name'] ?? null, Str::headline($type)),
                    'subtitle' => $this->firstString($payload['customer_email'] ?? null, $payload['nickname'] ?? null, $payload['customer_id'] ?? null),
                    'summary' => $this->firstString($payload['detail'] ?? null, $payload['name'] ?? null, $payload['product_sku'] ?? null),
                    'detail' => $this->valueSummary([
                        'customer_id' => $payload['customer_id'] ?? '',
                        'product_sku' => $payload['product_sku'] ?? '',
                        'visible' => (bool) ($payload['is_visible'] ?? false) ? 'yes' : 'no',
                        'moderation' => $this->valueSummary($payload['moderation'] ?? []),
                        'ratings' => $this->valueSummary($ratings),
                        'related_products' => $payload['related_product_skus'] ?? [],
                    ]),
                    'metric' => $type === 'review'
                        ? $this->firstString($payload['average_rating'] ?? null, 'No rating')
                        : $this->firstString($payload['uses_count'] ?? null, '0 uses'),
                    'customer_id' => (string) ($payload['customer_id'] ?? ''),
                    'product_sku' => $this->firstString($payload['product_sku'] ?? null),
                    'is_problem' => $status === 'pending' || ! (bool) ($payload['is_visible'] ?? true),
                    'action_label' => $type === 'review' ? 'Inspect Review' : 'Inspect Tag',
                ]);
            })
            ->values()
            ->all();
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
     * @param  list<array<string, mixed>>  $customerRows
     * @param  list<array<string, mixed>>  $addressRows
     * @param  list<array<string, mixed>>  $activityRows
     * @param  list<array<string, mixed>>  $moderationRows
     * @param  list<array<string, mixed>>  $problemRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $customerRows, array $addressRows, array $activityRows, array $moderationRows, array $problemRows): array
    {
        return match ($this->allowedSection()) {
            'addresses' => $addressRows,
            'activity' => $activityRows,
            'moderation' => $moderationRows,
            'problems' => $problemRows,
            default => $customerRows,
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
                        || $row['metric'] === $status
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
                    $row['customer_id'],
                    $row['product_sku'],
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

    private function attributeSummary(mixed $attributes): string
    {
        if (! is_array($attributes) || $attributes === []) {
            return '';
        }

        return collect($attributes)
            ->map(function (mixed $attribute): string {
                if (! is_array($attribute)) {
                    return (string) $attribute;
                }

                return $this->firstString($attribute['label'] ?? null, $attribute['code'] ?? null).': '.$this->valueSummary($attribute['values'] ?? []);
            })
            ->filter()
            ->implode(' / ');
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

    private function money(mixed $amount): string
    {
        if (! is_numeric($amount)) {
            return '';
        }

        return Number::currency((float) $amount, in: 'USD', locale: 'en_US');
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
        return in_array($this->status, ['', 'active', 'inactive', 'subscribed', 'unsubscribed', 'shared', 'empty_private', 'empty_guest', 'approved', 'pending', 'default_billing_shipping', 'additional', 'not_shared', 'guest_session_expired', 'problem'], true) ? $this->status : '';
    }

    private function allowedType(): string
    {
        return in_array($this->type, ['', 'customer', 'address', 'wishlist', 'compare', 'review', 'tag'], true) ? $this->type : '';
    }

    private function allowedSection(): string
    {
        return in_array($this->section, $this->allowedSections(), true) ? $this->section : 'customers';
    }

    private function allowedRole(): string
    {
        return in_array($this->role, ['customer', 'read-only', 'full', 'denied'], true) ? $this->role : 'customer';
    }

    /**
     * @return list<string>
     */
    private function allowedSections(): array
    {
        return ['customers', 'addresses', 'activity', 'moderation', 'problems'];
    }

    private function canViewCustomers(DomainPolicy $policy): bool
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
            'id' => 918,
            'name' => "{$this->allowedRole()} admin customer fixture",
            'email' => "{$this->allowedRole()}-admin-customer@example.test",
        ]);
        $user->setAttribute('role', $this->allowedRole());
        $user->exists = true;

        return $user;
    }
}
