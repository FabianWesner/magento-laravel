<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Policies\Modernization\Domain\DomainPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class AdminPromotionsWorkbench extends Component
{
    public string $storeView = '';

    public string $storeId = '';

    public string $query = '';

    public string $status = '';

    public string $type = '';

    public string $section = 'cart_rules';

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
        $this->section = 'cart_rules';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $this->storeId = $this->allowedStoreId();
        $this->storeView = $this->allowedStoreView();
        $this->status = $this->allowedStatus();
        $this->type = $this->allowedType();
        $this->section = $this->allowedSection();
        $this->role = $this->allowedRole();

        $canViewPromotions = $this->canViewPromotions($policy);
        $filters = $this->snapshotFilters();
        $cartRuleSnapshot = $canViewPromotions ? $queryService->snapshot('promotion_rule', $filters) : null;
        $catalogRuleSnapshot = $canViewPromotions ? $queryService->snapshot('catalog_price_rule', $filters) : null;
        $couponSnapshot = $canViewPromotions ? $queryService->snapshot('promotion_coupon', $filters) : null;
        $reportSnapshot = $canViewPromotions ? $queryService->snapshot('promotion_report', $filters) : null;

        $cartRuleRows = $this->cartRuleRows($this->snapshotRows($cartRuleSnapshot));
        $catalogRuleRows = $this->catalogRuleRows($this->snapshotRows($catalogRuleSnapshot));
        $couponRows = $this->couponRows($this->snapshotRows($couponSnapshot));
        $reportRows = $this->reportRows($this->snapshotRows($reportSnapshot));
        $problemRows = array_values(array_filter([...$cartRuleRows, ...$catalogRuleRows, ...$couponRows, ...$reportRows], fn (array $row): bool => (bool) $row['is_problem']));

        return view('livewire.admin-promotions-workbench', [
            'cartRuleFeature' => $catalog->get('promotion_rule'),
            'catalogRuleFeature' => $catalog->get('catalog_price_rule'),
            'couponFeature' => $catalog->get('promotion_coupon'),
            'reportFeature' => $catalog->get('promotion_report'),
            'featureIds' => array_values(array_unique([
                'AD-008',
                ...$catalog->get('promotion_rule')->featureIds,
                ...$catalog->get('catalog_price_rule')->featureIds,
                ...$catalog->get('promotion_coupon')->featureIds,
                ...$catalog->get('promotion_report')->featureIds,
            ])),
            'canViewPromotions' => $canViewPromotions,
            'cartRuleRows' => $cartRuleRows,
            'catalogRuleRows' => $catalogRuleRows,
            'couponRows' => $couponRows,
            'reportRows' => $reportRows,
            'problemRows' => $problemRows,
            'currentRows' => $this->filterRows($this->currentRows($cartRuleRows, $catalogRuleRows, $couponRows, $reportRows, $problemRows)),
            'activeFilters' => $this->activeFilters(),
            'snapshotStoreView' => $cartRuleSnapshot['store_view'] ?? null,
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
    private function cartRuleRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'active');

                return $this->baseRow($row, [
                    'domain' => 'promotion_rule',
                    'type' => 'cart_rule',
                    'type_label' => 'Cart rule',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['name'] ?? null, $payload['code'] ?? null, 'Cart rule'),
                    'subtitle' => $this->firstString($payload['coupon_code'] ?? null, $payload['rule_code'] ?? null, $payload['rule_id'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'discount' => $payload['discount'] ?? null,
                        'action' => $payload['simple_action'] ?? null,
                        'amount' => $payload['discount_amount'] ?? null,
                        'websites' => $payload['websites'] ?? null,
                        'groups' => $payload['customer_groups'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'conditions' => $payload['conditions'] ?? null,
                        'uses' => $payload['uses'] ?? null,
                        'stop_processing' => $payload['stop_rules_processing'] ?? null,
                        'validation' => $payload['validation_error'] ?? null,
                        'problem_type' => $payload['problem_type'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->dateRange($payload),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || in_array($status, ['invalid', 'expired'], true),
                    'action_label' => 'Inspect Cart Rule',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function catalogRuleRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) (($payload['apply_status'] ?? null) === 'stale'
                    ? 'stale'
                    : ($payload['status'] ?? 'scheduled'));

                return $this->baseRow($row, [
                    'domain' => 'catalog_price_rule',
                    'type' => 'catalog_rule',
                    'type_label' => 'Catalog rule',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['name'] ?? null, $payload['rule_id'] ?? null, 'Catalog rule'),
                    'subtitle' => $this->firstString($payload['rule_code'] ?? null, $payload['price_action'] ?? null, $payload['discount'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'discount' => $payload['discount'] ?? null,
                        'products' => $payload['product_count'] ?? null,
                        'websites' => $payload['websites'] ?? null,
                        'groups' => $payload['customer_groups'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'conditions' => $payload['conditions'] ?? null,
                        'last_applied_at' => $payload['last_applied_at'] ?? null,
                        'next_run_at' => $payload['next_run_at'] ?? null,
                        'problem' => $payload['problem'] ?? null,
                        'problem_type' => $payload['problem_type'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->dateRange($payload),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || in_array($status, ['stale', 'invalid'], true),
                    'action_label' => 'Inspect Catalog Rule',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function couponRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'generated');

                return $this->baseRow($row, [
                    'domain' => 'promotion_coupon',
                    'type' => 'coupon',
                    'type_label' => 'Coupon',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['coupon_code'] ?? null, $payload['code'] ?? null, 'Coupon'),
                    'subtitle' => $this->firstString($payload['rule_name'] ?? null, $payload['rule_code'] ?? null, $payload['rule_id'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'type' => $payload['coupon_type'] ?? null,
                        'uses' => $payload['uses'] ?? null,
                        'uses_per_coupon' => $payload['uses_per_coupon'] ?? null,
                        'uses_per_customer' => $payload['uses_per_customer'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'generated_at' => $payload['generated_at'] ?? null,
                        'expires_at' => $payload['expires_at'] ?? null,
                        'problem' => $payload['problem'] ?? null,
                        'problem_type' => $payload['problem_type'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->firstString($payload['customer_email'] ?? null),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || in_array($status, ['exhausted', 'expired'], true),
                    'action_label' => 'Inspect Coupon',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function reportRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'aggregated');

                return $this->baseRow($row, [
                    'domain' => 'promotion_report',
                    'type' => 'report',
                    'type_label' => 'Report',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'title' => $this->firstString($payload['name'] ?? null, $payload['report_key'] ?? null, $payload['report_type'] ?? null, 'Promotion report'),
                    'subtitle' => $this->firstString($payload['bucket'] ?? null, $payload['period'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'orders' => $payload['orders'] ?? null,
                        'uses' => $payload['uses'] ?? null,
                        'discount_amount' => $payload['discount_amount'] ?? $payload['total_discount'] ?? null,
                        'currency' => $payload['currency'] ?? null,
                        'rows' => $payload['rows_aggregated'] ?? null,
                        'coupons' => $payload['coupon_count'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'cron_job' => $payload['cron_job'] ?? null,
                        'cron' => $payload['cron'] ?? null,
                        'last_run_at' => $payload['last_run_at'] ?? null,
                        'problem' => $payload['problem'] ?? null,
                        'problem_type' => $payload['problem_type'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->firstString($payload['report_key'] ?? null),
                    'is_problem' => (bool) ($payload['is_problem'] ?? false) || in_array($status, ['stale', 'failed'], true),
                    'action_label' => 'Inspect Report',
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
     * @param  list<array<string, mixed>>  $cartRuleRows
     * @param  list<array<string, mixed>>  $catalogRuleRows
     * @param  list<array<string, mixed>>  $couponRows
     * @param  list<array<string, mixed>>  $reportRows
     * @param  list<array<string, mixed>>  $problemRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $cartRuleRows, array $catalogRuleRows, array $couponRows, array $reportRows, array $problemRows): array
    {
        return match ($this->allowedSection()) {
            'catalog_rules' => $catalogRuleRows,
            'coupons' => $couponRows,
            'reports' => $reportRows,
            'problems' => $problemRows,
            default => $cartRuleRows,
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

    private function dateRange(array $payload): string
    {
        $from = $this->firstString($payload['from_date'] ?? null, $payload['starts_at'] ?? null);
        $to = $this->firstString($payload['to_date'] ?? null, $payload['ends_at'] ?? null);

        if ($from === '' && $to === '') {
            return '';
        }

        return trim($from.' -> '.$to);
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
        return ['cart_rules', 'catalog_rules', 'coupons', 'reports', 'problems'];
    }

    private function allowedSection(): string
    {
        return in_array($this->section, $this->allowedSections(), true) ? $this->section : 'cart_rules';
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
        return in_array($this->status, ['', 'active', 'scheduled', 'inactive', 'expired', 'generated', 'exhausted', 'aggregated', 'stale', 'invalid', 'invalid_condition', 'failed', 'problem'], true)
            ? $this->status
            : '';
    }

    private function allowedType(): string
    {
        return in_array($this->type, ['', 'cart_rule', 'catalog_rule', 'coupon', 'report'], true) ? $this->type : '';
    }

    private function allowedRole(): string
    {
        return in_array($this->role, ['sales', 'catalog', 'read-only', 'full', 'denied'], true) ? $this->role : 'sales';
    }

    private function canViewPromotions(DomainPolicy $policy): bool
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
            'id' => 930,
            'name' => "{$this->role} admin promotions fixture",
            'email' => "{$this->role}-admin-promotions@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }
}
