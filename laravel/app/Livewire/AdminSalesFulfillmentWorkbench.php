<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Policies\Modernization\Domain\DomainPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class AdminSalesFulfillmentWorkbench extends Component
{
    public string $storeView = '';

    public string $storeId = '';

    public string $query = '';

    public string $status = '';

    public string $type = '';

    public string $section = 'orders';

    public string $role = 'sales';

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
        $this->section = 'orders';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $this->storeId = $this->allowedStoreId();
        $this->storeView = $this->allowedStoreView();
        $this->status = $this->allowedStatus();
        $this->type = $this->allowedType();
        $this->section = $this->allowedSection();
        $this->role = $this->allowedRole();

        $canViewSalesFulfillment = $this->canViewSalesFulfillment($policy);
        $filters = $this->snapshotFilters();
        $orderSnapshot = $canViewSalesFulfillment ? $queryService->snapshot('sales_order', $filters) : null;
        $invoiceSnapshot = $canViewSalesFulfillment ? $queryService->snapshot('sales_invoice', $filters) : null;
        $shipmentSnapshot = $canViewSalesFulfillment ? $queryService->snapshot('sales_shipment', $filters) : null;
        $creditMemoSnapshot = $canViewSalesFulfillment ? $queryService->snapshot('sales_credit_memo', $filters) : null;
        $transactionSnapshot = $canViewSalesFulfillment ? $queryService->snapshot('sales_transaction', $filters) : null;

        $orderRows = $this->orderRows($this->snapshotRows($orderSnapshot));
        $invoiceRows = $this->invoiceRows($this->snapshotRows($invoiceSnapshot));
        $shipmentRows = $this->shipmentRows($this->snapshotRows($shipmentSnapshot));
        $creditMemoRows = $this->creditMemoRows($this->snapshotRows($creditMemoSnapshot));
        $transactionRows = $this->transactionRows($this->snapshotRows($transactionSnapshot));
        $problemRows = array_values(array_filter([...$orderRows, ...$invoiceRows, ...$shipmentRows, ...$creditMemoRows, ...$transactionRows], fn (array $row): bool => (bool) $row['is_problem']));

        return view('livewire.admin-sales-fulfillment-workbench', [
            'orderFeature' => $catalog->get('sales_order'),
            'invoiceFeature' => $catalog->get('sales_invoice'),
            'shipmentFeature' => $catalog->get('sales_shipment'),
            'creditMemoFeature' => $catalog->get('sales_credit_memo'),
            'transactionFeature' => $catalog->get('sales_transaction'),
            'featureIds' => array_values(array_unique([
                'AD-005',
                'AD-006',
                ...$catalog->get('sales_order')->featureIds,
                ...$catalog->get('sales_invoice')->featureIds,
                ...$catalog->get('sales_shipment')->featureIds,
                ...$catalog->get('sales_credit_memo')->featureIds,
                ...$catalog->get('sales_transaction')->featureIds,
            ])),
            'canViewSalesFulfillment' => $canViewSalesFulfillment,
            'orderRows' => $orderRows,
            'invoiceRows' => $invoiceRows,
            'shipmentRows' => $shipmentRows,
            'creditMemoRows' => $creditMemoRows,
            'transactionRows' => $transactionRows,
            'problemRows' => $problemRows,
            'currentRows' => $this->filterRows($this->currentRows($orderRows, $invoiceRows, $shipmentRows, $creditMemoRows, $transactionRows, $problemRows)),
            'activeFilters' => $this->activeFilters(),
            'snapshotStoreView' => $orderSnapshot['store_view'] ?? null,
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
    private function orderRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? $payload['state'] ?? 'processing');

                return $this->baseRow($row, [
                    'domain' => 'sales_order',
                    'type' => 'order',
                    'type_label' => 'Order',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['increment_id'] ?? null, 'Order'),
                    'subtitle' => $this->firstString($payload['customer_email'] ?? null, $payload['customer_name'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'customer' => $payload['customer_name'] ?? null,
                        'grand_total' => $payload['grand_total'] ?? null,
                        'currency' => $payload['currency'] ?? null,
                        'items' => $payload['items_count'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'payment' => $payload['payment_method'] ?? null,
                        'shipping' => $payload['shipping_method'] ?? null,
                        'guards' => $payload['guards'] ?? [],
                        'comment' => $payload['last_comment'] ?? null,
                        'review' => $payload['review_reason'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->valueSummary(array_filter([
                        'invoices' => $payload['invoice_count'] ?? null,
                        'shipments' => $payload['shipment_count'] ?? null,
                        'credit_memos' => $payload['credit_memo_count'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || $status === 'payment_review',
                    'action_label' => 'Inspect Order',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function invoiceRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'paid');

                return $this->baseRow($row, [
                    'domain' => 'sales_invoice',
                    'type' => 'invoice',
                    'type_label' => 'Invoice',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['increment_id'] ?? null, 'Invoice'),
                    'subtitle' => $this->firstString($payload['order_increment_id'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'total' => $payload['total'] ?? null,
                        'currency' => $payload['currency'] ?? null,
                        'capture' => $payload['capture_type'] ?? null,
                        'items' => $payload['items_count'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'email_sent' => $payload['email_sent'] ?? null,
                        'pdf_available' => $payload['pdf_available'] ?? null,
                        'created_at' => $payload['created_at'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->firstString($payload['capture_transaction_id'] ?? null),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || $status === 'failed',
                    'action_label' => 'Inspect Invoice',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function shipmentRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'shipped');

                return $this->baseRow($row, [
                    'domain' => 'sales_shipment',
                    'type' => 'shipment',
                    'type_label' => 'Shipment',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['increment_id'] ?? null, 'Shipment'),
                    'subtitle' => $this->firstString($payload['order_increment_id'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'carrier' => $payload['carrier'] ?? null,
                        'tracking' => $payload['tracking_number'] ?? null,
                        'items' => $payload['items_count'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'email_sent' => $payload['email_sent'] ?? null,
                        'pdf_available' => $payload['pdf_available'] ?? null,
                        'problem' => $payload['problem'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->firstString($payload['shipped_at'] ?? null),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || $status === 'tracking_pending',
                    'action_label' => 'Inspect Shipment',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function creditMemoRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'refunded');

                return $this->baseRow($row, [
                    'domain' => 'sales_credit_memo',
                    'type' => 'credit_memo',
                    'type_label' => 'Credit memo',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['increment_id'] ?? null, 'Credit memo'),
                    'subtitle' => $this->firstString($payload['order_increment_id'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'amount' => $payload['amount'] ?? null,
                        'currency' => $payload['currency'] ?? null,
                        'refund_type' => $payload['refund_type'] ?? null,
                        'adjustment' => $payload['adjustment'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'email_sent' => $payload['email_sent'] ?? null,
                        'pdf_available' => $payload['pdf_available'] ?? null,
                        'failure' => $payload['failure_reason'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->firstString($payload['refund_transaction_id'] ?? null),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || $status === 'failed',
                    'action_label' => 'Inspect Credit Memo',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function transactionRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'captured');

                return $this->baseRow($row, [
                    'domain' => 'sales_transaction',
                    'type' => 'transaction',
                    'type_label' => 'Transaction',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['transaction_id'] ?? null, 'Transaction'),
                    'subtitle' => $this->firstString($payload['order_increment_id'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'kind' => $payload['kind'] ?? null,
                        'method' => $payload['payment_method'] ?? null,
                        'amount' => $payload['amount'] ?? null,
                        'currency' => $payload['currency'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'parent' => $payload['parent_transaction_id'] ?? null,
                        'message' => $payload['message'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->firstString($payload['gateway'] ?? null),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || in_array($status, ['failed', 'payment_review'], true),
                    'action_label' => 'Inspect Transaction',
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
     * @param  list<array<string, mixed>>  $orderRows
     * @param  list<array<string, mixed>>  $invoiceRows
     * @param  list<array<string, mixed>>  $shipmentRows
     * @param  list<array<string, mixed>>  $creditMemoRows
     * @param  list<array<string, mixed>>  $transactionRows
     * @param  list<array<string, mixed>>  $problemRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $orderRows, array $invoiceRows, array $shipmentRows, array $creditMemoRows, array $transactionRows, array $problemRows): array
    {
        return match ($this->allowedSection()) {
            'invoices' => $invoiceRows,
            'shipments' => $shipmentRows,
            'credit_memos' => $creditMemoRows,
            'transactions' => $transactionRows,
            'problems' => $problemRows,
            default => $orderRows,
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
        return ['orders', 'invoices', 'shipments', 'credit_memos', 'transactions', 'problems'];
    }

    private function allowedSection(): string
    {
        return in_array($this->section, $this->allowedSections(), true) ? $this->section : 'orders';
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
        return in_array($this->status, ['', 'pending', 'processing', 'complete', 'closed', 'canceled', 'holded', 'payment_review', 'paid', 'partial', 'shipped', 'tracking_pending', 'refunded', 'offline_refund', 'captured', 'authorization', 'failed', 'problem'], true)
            ? $this->status
            : '';
    }

    private function allowedType(): string
    {
        return in_array($this->type, ['', 'order', 'invoice', 'shipment', 'credit_memo', 'transaction'], true) ? $this->type : '';
    }

    private function allowedRole(): string
    {
        return in_array($this->role, ['sales', 'catalog', 'read-only', 'full', 'denied'], true) ? $this->role : 'sales';
    }

    private function canViewSalesFulfillment(DomainPolicy $policy): bool
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
            'id' => 920,
            'name' => "{$this->role} admin sales fulfillment fixture",
            'email' => "{$this->role}-admin-sales-fulfillment@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }
}
