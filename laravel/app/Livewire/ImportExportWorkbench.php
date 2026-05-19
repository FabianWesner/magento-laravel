<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Modernization\Domain\ImportExportDataflow;
use App\Policies\Modernization\Domain\DomainPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class ImportExportWorkbench extends Component
{
    public string $storeView = '';

    public string $storeId = '';

    public string $query = '';

    public string $status = '';

    public string $operation = '';

    public string $entity = '';

    public string $section = 'imports';

    public string $role = 'catalog';

    public function setSection(string $section): void
    {
        if (! in_array($section, ['imports', 'exports', 'profiles', 'files'], true)) {
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
        $this->operation = '';
        $this->entity = '';
        $this->section = 'imports';
    }

    public function render(
        DomainCatalog $catalog,
        DomainQueryService $queryService,
        DomainPolicy $policy,
        ImportExportDataflow $dataflow,
    ): View {
        $this->storeId = $this->allowedStoreId();
        $this->storeView = $this->allowedStoreView();
        $this->status = $this->allowedStatus();
        $this->operation = $this->allowedOperation();
        $this->entity = $this->allowedEntity();
        $this->section = $this->allowedSection();

        $canViewOperations = $this->canViewOperations($policy);
        $filters = $this->snapshotFilters();
        $importExportSnapshot = $canViewOperations ? $queryService->snapshot('import_export', $filters) : null;
        $dataflowSnapshot = $canViewOperations ? $queryService->snapshot('dataflow', $filters) : null;

        $importExportRows = $this->decorateRows($this->snapshotRows($importExportSnapshot), 'import_export');
        $dataflowRows = $this->decorateRows($this->snapshotRows($dataflowSnapshot), 'dataflow');
        $allRows = [...$importExportRows, ...$dataflowRows];

        $importRows = $this->filterRows($this->operationRows($allRows, 'import'));
        $exportRows = $this->filterRows($this->operationRows($allRows, 'export'));
        $profileRows = $this->filterRows($dataflowRows);
        $fileRows = $this->filterRows($this->fileRows($allRows));
        $currentRows = $this->currentRows($importRows, $exportRows, $profileRows, $fileRows);
        $selectedProfile = (string) (($currentRows[0]['profile_name'] ?? 'catalog-product-import'));
        $selectedStoreView = $this->allowedStoreView() !== '' ? $this->allowedStoreView() : 'default';

        return view('livewire.import-export-workbench', [
            'importExportFeature' => $catalog->get('import_export'),
            'dataflowFeature' => $catalog->get('dataflow'),
            'featureIds' => array_values(array_unique([
                ...$catalog->get('import_export')->featureIds,
                ...$catalog->get('dataflow')->featureIds,
            ])),
            'canViewOperations' => $canViewOperations,
            'importRows' => $importRows,
            'exportRows' => $exportRows,
            'profileRows' => $profileRows,
            'fileRows' => $fileRows,
            'currentRows' => $currentRows,
            'activeFilters' => $this->activeFilters(),
            'validationPlan' => $dataflow->validateCsvRows([
                ['sku' => 'simple-shirt', 'store_view' => $selectedStoreView],
            ]),
            'errorPlan' => $dataflow->errorFile(Str::slug($selectedProfile)),
            'snapshotStoreView' => $importExportSnapshot['store_view'] ?? null,
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
        $operation = $this->operation($payload, $domain);
        $status = (string) ($payload['status'] ?? 'ready');
        $rowsFailed = (int) ($payload['rows_failed'] ?? 0);
        $errors = $payload['errors'] ?? [];
        $errorFile = $this->firstString($payload['error_file'] ?? null);

        return [
            'domain' => $domain,
            'operation' => $operation,
            'operation_label' => Str::headline($operation),
            'entity_id' => (int) ($row['entity_id'] ?? 0),
            'store_id' => (int) ($row['store_id'] ?? 0),
            'store_view' => (string) ($row['store_view'] ?? ''),
            'title' => $this->title($payload, $operation),
            'status' => $status,
            'status_label' => Str::headline($status),
            'entity_type' => (string) ($payload['entity_type'] ?? 'catalog_product'),
            'entity_label' => Str::headline((string) ($payload['entity_type'] ?? 'catalog_product')),
            'profile_name' => $this->firstString($payload['profile_name'] ?? null, $payload['title'] ?? null),
            'profile_id' => $this->firstString($payload['profile_id'] ?? null),
            'batch_id' => $this->firstString($payload['batch_id'] ?? null),
            'adapter' => $this->firstString($payload['adapter'] ?? null),
            'format' => $this->firstString($payload['format'] ?? null, $payload['parser'] ?? null, 'CSV'),
            'behavior' => $this->firstString($payload['behavior'] ?? null, $payload['direction'] ?? null),
            'rows_total' => (int) ($payload['rows_total'] ?? 0),
            'rows_processed' => (int) ($payload['rows_processed'] ?? 0),
            'rows_failed' => $rowsFailed,
            'file_path' => $this->firstString($payload['error_file'] ?? null, $payload['file_path'] ?? null),
            'error_file' => $errorFile,
            'schedule' => $this->firstString($payload['scheduled_at'] ?? null, $payload['last_run_at'] ?? null),
            'validation' => $this->firstString($payload['validation'] ?? null),
            'summary' => $this->summary($payload, $operation),
            'errors' => $this->valueSummary($errors),
            'actions' => $this->valueSummary($payload['actions'] ?? []),
            'is_problem' => $rowsFailed > 0 || $errorFile !== '' || in_array($status, ['failed', 'blocked', 'warning'], true) || $errors !== [],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function operation(array $payload, string $domain): string
    {
        if (($payload['direction'] ?? '') === 'import' || ($payload['direction'] ?? '') === 'export') {
            return (string) $payload['direction'];
        }

        return (string) ($payload['operation'] ?? ($domain === 'dataflow' ? 'profile' : 'import'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function title(array $payload, string $operation): string
    {
        return $this->firstString(
            $payload['title'] ?? null,
            $payload['profile_name'] ?? null,
            $payload['entity_type'] ?? null,
            Str::headline($operation),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function summary(array $payload, string $operation): string
    {
        $ui = $this->nestedArray($payload, 'ui');

        return $this->firstString(
            $ui['summary'] ?? null,
            $payload['summary'] ?? null,
            $payload['description'] ?? null,
            Str::headline($operation),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function operationRows(array $rows, string $operation): array
    {
        return collect($rows)
            ->filter(fn (array $row): bool => $row['operation'] === $operation)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function fileRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row): bool => $row['file_path'] !== '' || $row['error_file'] !== '')
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
        $operation = $this->allowedOperation();
        $entity = $this->allowedEntity();

        return collect($rows)
            ->filter(function (array $row) use ($query, $status, $operation, $entity): bool {
                if ($status !== '' && $row['status'] !== $status && ($status !== 'problem' || ! $row['is_problem'])) {
                    return false;
                }

                if ($operation !== '' && $row['operation'] !== $operation && ($operation !== 'profile' || $row['domain'] !== 'dataflow')) {
                    return false;
                }

                if ($entity !== '' && $row['entity_type'] !== $entity) {
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
            $row['operation_label'] ?? '',
            $row['title'] ?? '',
            $row['status_label'] ?? '',
            $row['entity_label'] ?? '',
            $row['profile_name'] ?? '',
            $row['profile_id'] ?? '',
            $row['batch_id'] ?? '',
            $row['adapter'] ?? '',
            $row['format'] ?? '',
            $row['behavior'] ?? '',
            $row['file_path'] ?? '',
            $row['error_file'] ?? '',
            $row['validation'] ?? '',
            $row['summary'] ?? '',
            $row['errors'] ?? '',
            $row['actions'] ?? '',
        ])));
    }

    /**
     * @param  list<array<string, mixed>>  $importRows
     * @param  list<array<string, mixed>>  $exportRows
     * @param  list<array<string, mixed>>  $profileRows
     * @param  list<array<string, mixed>>  $fileRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $importRows, array $exportRows, array $profileRows, array $fileRows): array
    {
        return match ($this->allowedSection()) {
            'exports' => $exportRows,
            'profiles' => $profileRows,
            'files' => $fileRows,
            default => $importRows,
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
            'operation' => $this->allowedOperation(),
            'entity' => $this->allowedEntity(),
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
        return in_array($this->status, [
            '',
            'validated',
            'generated',
            'scheduled',
            'completed',
            'processing',
            'failed',
            'blocked',
            'problem',
        ], true) ? $this->status : '';
    }

    private function allowedOperation(): string
    {
        return in_array($this->operation, ['', 'import', 'export', 'profile'], true) ? $this->operation : '';
    }

    private function allowedEntity(): string
    {
        return in_array($this->entity, ['', 'catalog_product', 'customer', 'stock'], true) ? $this->entity : '';
    }

    private function allowedSection(): string
    {
        return in_array($this->section, ['imports', 'exports', 'profiles', 'files'], true) ? $this->section : 'imports';
    }

    private function canViewOperations(DomainPolicy $policy): bool
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
            'name' => "{$this->role} import export fixture",
            'email' => "{$this->role}-import-export@example.test",
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
}
