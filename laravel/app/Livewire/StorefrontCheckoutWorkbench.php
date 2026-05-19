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

class StorefrontCheckoutWorkbench extends Component
{
    public string $storeView = '';

    public string $storeId = '';

    public string $query = '';

    public string $status = '';

    public string $type = '';

    public string $section = 'steps';

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
        $this->section = 'steps';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $this->storeId = $this->allowedStoreId();
        $this->storeView = $this->allowedStoreView();
        $this->status = $this->allowedStatus();
        $this->type = $this->allowedType();
        $this->section = $this->allowedSection();
        $this->role = $this->allowedRole();

        $canViewCheckout = $this->canViewCheckout($policy);
        $filters = $this->snapshotFilters();
        $stepSnapshot = $canViewCheckout ? $queryService->snapshot('checkout_step', $filters) : null;
        $paymentSnapshot = $canViewCheckout ? $queryService->snapshot('checkout_payment', $filters) : null;
        $reviewSnapshot = $canViewCheckout ? $queryService->snapshot('checkout_review', $filters) : null;
        $multishippingSnapshot = $canViewCheckout ? $queryService->snapshot('multishipping', $filters) : null;

        $stepRows = $this->stepRows($this->snapshotRows($stepSnapshot));
        $paymentRows = $this->paymentRows($this->snapshotRows($paymentSnapshot));
        $reviewRows = $this->reviewRows($this->snapshotRows($reviewSnapshot));
        $multishippingRows = $this->multishippingRows($this->snapshotRows($multishippingSnapshot));
        $problemRows = array_values(array_filter([...$stepRows, ...$paymentRows, ...$reviewRows, ...$multishippingRows], fn (array $row): bool => (bool) $row['is_problem']));

        return view('livewire.storefront-checkout-workbench', [
            'stepFeature' => $catalog->get('checkout_step'),
            'paymentFeature' => $catalog->get('checkout_payment'),
            'reviewFeature' => $catalog->get('checkout_review'),
            'multishippingFeature' => $catalog->get('multishipping'),
            'featureIds' => array_values(array_unique([
                'SF-008',
                ...$catalog->get('checkout_step')->featureIds,
                ...$catalog->get('checkout_payment')->featureIds,
                ...$catalog->get('checkout_review')->featureIds,
                ...$catalog->get('multishipping')->featureIds,
            ])),
            'canViewCheckout' => $canViewCheckout,
            'stepRows' => $stepRows,
            'paymentRows' => $paymentRows,
            'reviewRows' => $reviewRows,
            'multishippingRows' => $multishippingRows,
            'problemRows' => $problemRows,
            'currentRows' => $this->filterRows($this->currentRows($stepRows, $paymentRows, $reviewRows, $multishippingRows, $problemRows)),
            'activeFilters' => $this->activeFilters(),
            'snapshotStoreView' => $stepSnapshot['store_view'] ?? null,
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
    private function stepRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'ready');

                return $this->baseRow($row, [
                    'domain' => 'checkout_step',
                    'type' => 'step',
                    'type_label' => 'Checkout step',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['title'] ?? null, $payload['step'] ?? null, 'Checkout step'),
                    'subtitle' => $this->firstString($payload['quote_code'] ?? null, $payload['checkout_method'] ?? null, $payload['customer_label'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'step' => $payload['step'] ?? null,
                        'method' => $payload['checkout_method'] ?? null,
                        'address_state' => $payload['address_state'] ?? null,
                        'shipping_method' => $payload['shipping_method'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'progress' => $payload['progress'] ?? null,
                        'form_key_required' => $payload['form_key_required'] ?? null,
                        'message' => $payload['message'] ?? null,
                        'writes_disabled' => $payload['writes_disabled'] ?? null,
                        'problem_type' => $payload['problem_type'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->firstString($payload['step'] ?? null),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || in_array($status, ['invalid_address', 'method_required', 'agreement_required'], true),
                    'action_label' => 'Inspect Step',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function paymentRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'ready');

                return $this->baseRow($row, [
                    'domain' => 'checkout_payment',
                    'type' => 'payment',
                    'type_label' => 'Payment method',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['title'] ?? null, $payload['method_title'] ?? null, 'Payment method'),
                    'subtitle' => $this->firstString($payload['method_code'] ?? null, $payload['quote_code'] ?? null, $payload['gateway'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'method' => $payload['method_title'] ?? null,
                        'field' => $payload['field_name'] ?? null,
                        'gateway' => $payload['gateway'] ?? null,
                        'redirect_required' => $payload['redirect_required'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'sandbox' => $payload['sandbox'] ?? null,
                        'failure_reason' => $payload['failure_reason'] ?? null,
                        'form_key_required' => $payload['form_key_required'] ?? null,
                        'writes_disabled' => $payload['writes_disabled'] ?? null,
                        'problem_type' => $payload['problem_type'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->firstString($payload['method_code'] ?? null),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || in_array($status, ['failed_payment', 'payment_review'], true),
                    'action_label' => 'Inspect Payment',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function reviewRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'ready');
                $currency = $payload['currency'] ?? 'USD';

                return $this->baseRow($row, [
                    'domain' => 'checkout_review',
                    'type' => 'review',
                    'type_label' => 'Order review',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['title'] ?? null, $payload['quote_code'] ?? null, 'Order review'),
                    'subtitle' => $this->firstString($payload['quote_code'] ?? null, $payload['disabled_reason'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'subtotal' => $this->money($payload['subtotal'] ?? null, $currency),
                        'discount' => $this->money($payload['discount'] ?? null, $currency),
                        'tax' => $this->money($payload['tax'] ?? null, $currency),
                        'shipping' => $this->money($payload['shipping'] ?? null, $currency),
                        'grand_total' => $this->money($payload['grand_total'] ?? null, $currency),
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'agreements_required' => $payload['agreements_required'] ?? null,
                        'agreements_accepted' => $payload['agreements_accepted'] ?? null,
                        'place_order_allowed' => $payload['place_order_allowed'] ?? null,
                        'disabled_reason' => $payload['disabled_reason'] ?? null,
                        'success_quote_id' => $payload['success_quote_id'] ?? null,
                        'writes_disabled' => $payload['writes_disabled'] ?? null,
                        'problem_type' => $payload['problem_type'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->money($payload['grand_total'] ?? null, $currency),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || $status === 'agreement_required',
                    'action_label' => 'Inspect Review',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function multishippingRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'addresses');

                return $this->baseRow($row, [
                    'domain' => 'multishipping',
                    'type' => 'multishipping',
                    'type_label' => 'Multishipping',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['title'] ?? null, $payload['quote_code'] ?? null, 'Multishipping'),
                    'subtitle' => $this->firstString($payload['quote_code'] ?? null, $payload['customer_label'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'addresses' => $payload['address_count'] ?? null,
                        'shipping_methods' => $payload['shipping_method_count'] ?? null,
                        'payment_method' => $payload['payment_method'] ?? null,
                        'orders_preview' => $payload['order_count_preview'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'requires_login' => $payload['requires_login'] ?? null,
                        'virtual_allowed' => $payload['virtual_allowed'] ?? null,
                        'is_multi_shipping' => $payload['is_multi_shipping'] ?? null,
                        'form_key_required' => $payload['form_key_required'] ?? null,
                        'message' => $payload['message'] ?? null,
                        'writes_disabled' => $payload['writes_disabled'] ?? null,
                        'problem_type' => $payload['problem_type'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->firstString($payload['order_count_preview'] ?? null).' orders',
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || $status === 'blocked',
                    'action_label' => 'Inspect Multishipping',
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
     * @param  list<array<string, mixed>>  $stepRows
     * @param  list<array<string, mixed>>  $paymentRows
     * @param  list<array<string, mixed>>  $reviewRows
     * @param  list<array<string, mixed>>  $multishippingRows
     * @param  list<array<string, mixed>>  $problemRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $stepRows, array $paymentRows, array $reviewRows, array $multishippingRows, array $problemRows): array
    {
        return match ($this->allowedSection()) {
            'payments' => $paymentRows,
            'review' => $reviewRows,
            'multishipping' => $multishippingRows,
            'problems' => $problemRows,
            default => $stepRows,
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
        return ['steps', 'payments', 'review', 'multishipping', 'problems'];
    }

    private function allowedSection(): string
    {
        return in_array($this->section, $this->allowedSections(), true) ? $this->section : 'steps';
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
        return in_array($this->status, ['', 'ready', 'invalid_address', 'method_required', 'agreement_required', 'payment_review', 'failed_payment', 'blocked', 'addresses', 'methods', 'overview', 'problem'], true)
            ? $this->status
            : '';
    }

    private function allowedType(): string
    {
        return in_array($this->type, ['', 'step', 'payment', 'review', 'multishipping'], true) ? $this->type : '';
    }

    private function allowedRole(): string
    {
        return in_array($this->role, ['customer', 'read-only', 'full', 'denied'], true) ? $this->role : 'customer';
    }

    private function canViewCheckout(DomainPolicy $policy): bool
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
            'id' => 941,
            'name' => "{$this->role} storefront checkout fixture",
            'email' => "{$this->role}-storefront-checkout@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }
}
