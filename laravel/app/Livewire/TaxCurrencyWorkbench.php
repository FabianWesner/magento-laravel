<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Policies\Modernization\Domain\DomainPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class TaxCurrencyWorkbench extends Component
{
    public string $storeView = '';

    public string $storeId = '';

    public string $query = '';

    public string $status = '';

    public string $type = '';

    public string $section = 'tax';

    public string $role = 'catalog';

    public function setSection(string $section): void
    {
        if (! in_array($section, ['tax', 'currency', 'jobs', 'problems'], true)) {
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
        $this->section = 'tax';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $this->storeId = $this->allowedStoreId();
        $this->storeView = $this->allowedStoreView();
        $this->status = $this->allowedStatus();
        $this->type = $this->allowedType();
        $this->section = $this->allowedSection();

        $canViewTaxCurrency = $this->canViewTaxCurrency($policy);
        $filters = $this->snapshotFilters();
        $taxSnapshot = $canViewTaxCurrency ? $queryService->snapshot('tax', $filters) : null;
        $currencySnapshot = $canViewTaxCurrency ? $queryService->snapshot('currency', $filters) : null;

        $taxRows = $this->filterRows($this->decorateRows($this->snapshotRows($taxSnapshot), 'tax'));
        $currencyRows = $this->filterRows($this->decorateRows($this->snapshotRows($currencySnapshot), 'currency'));
        $allRows = [...$taxRows, ...$currencyRows];
        $jobRows = $this->jobRows($allRows);
        $problemRows = $this->problemRows($allRows);

        return view('livewire.tax-currency-workbench', [
            'taxFeature' => $catalog->get('tax'),
            'currencyFeature' => $catalog->get('currency'),
            'featureIds' => array_values(array_unique([
                ...$catalog->get('tax')->featureIds,
                ...$catalog->get('currency')->featureIds,
            ])),
            'canViewTaxCurrency' => $canViewTaxCurrency,
            'taxRows' => $taxRows,
            'currencyRows' => $currencyRows,
            'jobRows' => $jobRows,
            'problemRows' => $problemRows,
            'currentRows' => $this->currentRows($taxRows, $currencyRows, $jobRows, $problemRows),
            'activeFilters' => $this->activeFilters(),
            'snapshotStoreView' => $taxSnapshot['store_view'] ?? null,
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
    private function decorateRows(array $rows, string $domain): array
    {
        return collect($rows)
            ->map(fn (array $row): array => $this->decorateRow($row, $domain))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function decorateRow(array $row, string $domain): array
    {
        $payload = $row['payload'] ?? [];
        $kind = (string) ($payload['kind'] ?? $domain);
        $status = (string) ($payload['status'] ?? 'active');
        $isProblem = $status === 'invalid'
            || $status === 'stale'
            || $status === 'failed'
            || (bool) ($payload['is_stale'] ?? false)
            || $this->firstString($payload['error'] ?? null) !== '';

        return [
            'domain' => $domain,
            'domain_label' => Str::headline($domain),
            'kind' => $kind,
            'kind_label' => Str::headline($kind),
            'entity_id' => (int) ($row['entity_id'] ?? 0),
            'store_id' => (int) ($row['store_id'] ?? 0),
            'store_view' => (string) ($row['store_view'] ?? ''),
            'title' => $this->firstString($payload['label'] ?? null, $kind),
            'status' => $status,
            'status_label' => Str::headline($status),
            'summary' => $this->summary($payload, $kind),
            'primary_value' => $this->primaryValue($payload, $domain),
            'detail' => $this->detail($payload, $domain),
            'scope' => $this->scope($payload),
            'schedule' => $this->schedule($payload),
            'error' => $this->firstString($payload['error'] ?? null, $payload['stale_reason'] ?? null),
            'cron_job' => $this->firstString($payload['cron_job'] ?? null),
            'report_key' => $this->firstString($payload['report_key'] ?? null),
            'is_problem' => $isProblem,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function summary(array $payload, string $kind): string
    {
        $ui = $this->nestedArray($payload, 'ui');

        return $this->firstString(
            $ui['summary'] ?? null,
            $payload['summary'] ?? null,
            Str::headline($kind),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function primaryValue(array $payload, string $domain): string
    {
        if ($domain === 'tax') {
            return $this->firstString(
                array_key_exists('rate_percent', $payload) ? $this->percentLabel((float) $payload['rate_percent']) : null,
                array_key_exists('tax_amount', $payload) ? $this->moneyLabel((float) $payload['tax_amount'], 'USD') : null,
                array_key_exists('product_tax_class', $payload) ? $payload['product_tax_class'].' / '.($payload['customer_tax_class'] ?? '') : null,
            );
        }

        return $this->firstString(
            array_key_exists('rate', $payload) ? $this->rateLabel((float) $payload['rate']) : null,
            array_key_exists('display_currency', $payload) ? $payload['base_currency'].' -> '.$payload['display_currency'] : null,
            $payload['symbol'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function detail(array $payload, string $domain): string
    {
        $details = $domain === 'tax'
            ? [
                'country' => $payload['tax_country_id'] ?? null,
                'region' => $payload['tax_region_code'] ?? null,
                'postcode' => $payload['tax_postcode'] ?? null,
                'customer_class' => $payload['customer_tax_class'] ?? null,
                'product_class' => $payload['product_tax_class'] ?? null,
                'rates' => $payload['rates'] ?? null,
                'combinations' => $payload['combinations'] ?? null,
                'taxable_amount' => array_key_exists('taxable_amount', $payload) ? $this->moneyLabel((float) $payload['taxable_amount'], 'USD') : null,
                'discount' => array_key_exists('discount_amount', $payload) ? $this->moneyLabel((float) $payload['discount_amount'], 'USD') : null,
                'rounding_delta' => array_key_exists('rounding_delta', $payload) ? number_format((float) $payload['rounding_delta'], 2) : null,
                'table' => $payload['table'] ?? null,
            ]
            : [
                'from' => $payload['currency_from'] ?? null,
                'to' => $payload['currency_to'] ?? null,
                'base' => $payload['base_currency'] ?? null,
                'display' => $payload['display_currency'] ?? null,
                'allowed' => $payload['allowed_currencies'] ?? null,
                'provider' => $payload['provider'] ?? $payload['import_service'] ?? null,
                'imported_at' => $payload['imported_at'] ?? null,
                'table' => $payload['table'] ?? null,
            ];

        return $this->valueSummary(array_filter($details, fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function scope(array $payload): string
    {
        return $this->valueSummary(array_filter([
            'scope' => $payload['scope'] ?? null,
            'inherited' => (bool) ($payload['inherited'] ?? false),
            'price_includes_tax' => (bool) ($payload['price_includes_tax'] ?? false),
            'after_discount' => (bool) ($payload['calculate_after_discount'] ?? false),
            'shipping_taxable' => (bool) ($payload['shipping_taxable'] ?? false),
            'cross_border_trade' => (bool) ($payload['cross_border_trade'] ?? false),
        ], fn (mixed $value): bool => $value !== null && $value !== false && $value !== ''));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function schedule(array $payload): string
    {
        return $this->valueSummary(array_filter([
            'cron_job' => $payload['cron_job'] ?? null,
            'schedule' => $payload['schedule'] ?? null,
            'last_run' => $payload['last_run_at'] ?? null,
            'next_run' => $payload['next_run_at'] ?? null,
            'report_bucket' => $payload['report_bucket'] ?? null,
            'row_count' => $payload['row_count'] ?? null,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function jobRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row): bool => $row['cron_job'] !== '' || $row['report_key'] !== '' || $row['schedule'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function problemRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row): bool => $row['is_problem'])
            ->values()
            ->all();
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
                if ($status !== '' && $row['status'] !== $status && ($status !== 'problem' || ! $row['is_problem'])) {
                    return false;
                }

                if ($type !== '' && $row['kind'] !== $type && $row['domain'] !== $type) {
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
            $row['domain_label'] ?? '',
            $row['kind_label'] ?? '',
            $row['title'] ?? '',
            $row['status_label'] ?? '',
            $row['summary'] ?? '',
            $row['primary_value'] ?? '',
            $row['detail'] ?? '',
            $row['scope'] ?? '',
            $row['schedule'] ?? '',
            $row['error'] ?? '',
        ])));
    }

    /**
     * @param  list<array<string, mixed>>  $taxRows
     * @param  list<array<string, mixed>>  $currencyRows
     * @param  list<array<string, mixed>>  $jobRows
     * @param  list<array<string, mixed>>  $problemRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $taxRows, array $currencyRows, array $jobRows, array $problemRows): array
    {
        return match ($this->allowedSection()) {
            'currency' => $currencyRows,
            'jobs' => $jobRows,
            'problems' => $problemRows,
            default => $taxRows,
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
        return in_array($this->status, ['', 'active', 'valid', 'invalid', 'current', 'stale', 'scheduled', 'failed', 'aggregated', 'problem'], true) ? $this->status : '';
    }

    private function allowedType(): string
    {
        return in_array($this->type, ['', 'tax', 'currency', 'class', 'rate', 'rule', 'calculation', 'report', 'config', 'currency_rate', 'symbol', 'job'], true) ? $this->type : '';
    }

    private function allowedSection(): string
    {
        return in_array($this->section, ['tax', 'currency', 'jobs', 'problems'], true) ? $this->section : 'tax';
    }

    private function canViewTaxCurrency(DomainPolicy $policy): bool
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
            'name' => "{$this->role} tax currency fixture",
            'email' => "{$this->role}-tax-currency@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function nestedArray(array $payload, string $key): array
    {
        return is_array($payload[$key] ?? null) ? $payload[$key] : [];
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

    private function percentLabel(float $value): string
    {
        return number_format($value, 2).'%';
    }

    private function rateLabel(float $value): string
    {
        return number_format($value, 4);
    }

    private function moneyLabel(float $value, string $currency): string
    {
        return $currency.' '.number_format($value, 2);
    }
}
