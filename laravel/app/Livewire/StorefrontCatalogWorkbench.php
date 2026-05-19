<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Policies\Modernization\Domain\DomainPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class StorefrontCatalogWorkbench extends Component
{
    public string $storeView = 'default';

    public string $storeId = '';

    public string $category = '';

    public string $type = '';

    public string $stockState = '';

    public string $swatch = '';

    public string $query = '';

    public string $sort = 'name';

    public string $sortDirection = 'asc';

    public string $limit = 'all';

    public string $mode = 'grid';

    public string $role = 'catalog';

    public function setMode(string $mode): void
    {
        if (! in_array($mode, ['grid', 'list'], true)) {
            return;
        }

        $this->mode = $mode;
    }

    public function clearFilters(): void
    {
        $this->storeView = 'default';
        $this->storeId = '';
        $this->category = '';
        $this->type = '';
        $this->stockState = '';
        $this->swatch = '';
        $this->query = '';
        $this->sort = 'name';
        $this->sortDirection = 'asc';
        $this->limit = 'all';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $canViewCatalog = $this->canViewCatalog($policy);
        $snapshot = $canViewCatalog ? $queryService->snapshot('catalog', $this->snapshotFilters()) : null;
        $allRows = $snapshot['payload']['rows'] ?? [];
        $rows = $this->filteredRows($allRows);

        return view('livewire.storefront-catalog-workbench', [
            'feature' => $catalog->get('catalog'),
            'canViewCatalog' => $canViewCatalog,
            'rows' => $rows,
            'allRows' => $allRows,
            'categories' => $this->payloadOptions($allRows, 'category'),
            'types' => $this->payloadOptions($allRows, 'type'),
            'swatches' => $this->payloadOptions($allRows, 'swatch'),
            'activeFilters' => $this->activeFilters(),
            'snapshotStoreView' => $snapshot['store_view'] ?? null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotFilters(): array
    {
        return array_filter([
            'store_id' => $this->storeId,
            'store_view' => $this->storeView,
        ], fn (mixed $value): bool => $value !== '' && $value !== null);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function filteredRows(array $rows): array
    {
        $filtered = collect($rows)
            ->filter(function (array $row): bool {
                $payload = $row['payload'] ?? [];

                if ($this->category !== '' && ($payload['category'] ?? '') !== $this->category) {
                    return false;
                }

                if ($this->type !== '' && ($payload['type'] ?? '') !== $this->type) {
                    return false;
                }

                if ($this->swatch !== '' && ($payload['swatch'] ?? '') !== $this->swatch) {
                    return false;
                }

                if ($this->stockState === 'in_stock' && (int) ($payload['stock'] ?? 0) <= 0) {
                    return false;
                }

                if ($this->stockState === 'out_of_stock' && (int) ($payload['stock'] ?? 0) > 0) {
                    return false;
                }

                if ($this->query !== '') {
                    $haystack = Str::lower(implode(' ', [
                        $payload['name'] ?? '',
                        $payload['sku'] ?? '',
                        $payload['short_description'] ?? '',
                    ]));

                    return Str::contains($haystack, Str::lower($this->query));
                }

                return true;
            });

        $sorter = match ($this->sort) {
            'price' => fn (array $row): float => (float) ($row['payload']['price'] ?? 0),
            'rating' => fn (array $row): float => (float) ($row['payload']['rating'] ?? 0),
            'stock' => fn (array $row): int => (int) ($row['payload']['stock'] ?? 0),
            default => fn (array $row): string => (string) ($row['payload']['name'] ?? ''),
        };

        $sorted = $this->sortDirection === 'desc'
            ? $filtered->sortByDesc($sorter)
            : $filtered->sortBy($sorter);

        $rows = $sorted->values();

        if ($this->limit !== 'all') {
            $rows = $rows->take((int) $this->limit);
        }

        return $rows->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<string>
     */
    private function payloadOptions(array $rows, string $key): array
    {
        return collect($rows)
            ->pluck("payload.{$key}")
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
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
            'store' => $this->storeView,
            'category' => $this->category,
            'type' => $this->type,
            'stock' => $this->stockState,
            'swatch' => $this->swatch,
            'search' => $this->query,
            'limit' => $this->limit === 'all' ? '' : $this->limit,
        ], fn (string $value): bool => $value !== '');
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
            'id' => 903,
            'name' => "{$this->role} catalog fixture",
            'email' => "{$this->role}-catalog@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }
}
