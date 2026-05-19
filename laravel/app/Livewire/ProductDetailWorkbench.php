<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Policies\Modernization\Domain\DomainPolicy;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProductDetailWorkbench extends Component
{
    public string $storeView = 'default';

    public string $storeId = '';

    public string $productId = '';

    public string $productType = '';

    public string $section = 'overview';

    public string $role = 'catalog';

    public function setSection(string $section): void
    {
        if (! in_array($section, ['overview', 'media', 'options', 'commerce'], true)) {
            return;
        }

        $this->section = $section;
    }

    public function clearFilters(): void
    {
        $this->storeView = 'default';
        $this->storeId = '';
        $this->productId = '';
        $this->productType = '';
        $this->section = 'overview';
    }

    public function updatedStoreView(): void
    {
        $this->productId = '';
        $this->section = 'overview';
    }

    public function updatedStoreId(): void
    {
        $this->productId = '';
        $this->section = 'overview';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $canViewProduct = $this->canViewProduct($policy);
        $productSnapshot = $canViewProduct ? $queryService->snapshot('product', $this->snapshotFilters()) : null;
        $mediaSnapshot = $canViewProduct ? $queryService->snapshot('product_media', $this->snapshotFilters()) : null;
        $downloadableSnapshot = $canViewProduct ? $queryService->snapshot('downloadable', $this->snapshotFilters()) : null;
        $allProducts = $this->snapshotRows($productSnapshot);
        $products = $this->filteredProducts($allProducts);
        $selectedProduct = $products[0]['payload'] ?? [];
        $selectedEntityId = (int) ($products[0]['entity_id'] ?? 0);
        $mediaRows = $this->relatedRows($this->snapshotRows($mediaSnapshot), $selectedProduct, 'media_entity_ids');
        $downloadRows = $this->relatedRows($this->snapshotRows($downloadableSnapshot), $selectedProduct, 'downloadable_entity_ids');

        return view('livewire.product-detail-workbench', [
            'feature' => $catalog->get('product'),
            'mediaFeature' => $catalog->get('product_media'),
            'downloadableFeature' => $catalog->get('downloadable'),
            'featureIds' => array_values(array_unique([
                ...$catalog->get('product')->featureIds,
                ...$catalog->get('product_media')->featureIds,
                ...$catalog->get('downloadable')->featureIds,
            ])),
            'canViewProduct' => $canViewProduct,
            'products' => $products,
            'productOptions' => $allProducts,
            'selectedProduct' => $selectedProduct,
            'selectedEntityId' => $selectedEntityId,
            'mediaRows' => $mediaRows,
            'downloadRows' => $downloadRows,
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
            'store_id' => $this->storeId,
            'store_view' => $this->storeView,
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
     * @param  list<array<string, mixed>>  $products
     * @return list<array<string, mixed>>
     */
    private function filteredProducts(array $products): array
    {
        return collect($products)
            ->filter(function (array $product): bool {
                $payload = $product['payload'] ?? [];

                if ($this->productId !== '' && (string) ($product['entity_id'] ?? '') !== $this->productId) {
                    return false;
                }

                if ($this->productType !== '' && (string) ($payload['type'] ?? '') !== $this->productType) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $selectedProduct
     * @return list<array<string, mixed>>
     */
    private function relatedRows(array $rows, array $selectedProduct, string $relationKey): array
    {
        if ($selectedProduct === []) {
            return [];
        }

        $relatedEntityIds = array_map('intval', $selectedProduct[$relationKey] ?? []);
        $sku = (string) ($selectedProduct['sku'] ?? '');

        return collect($rows)
            ->filter(function (array $row) use ($relatedEntityIds, $sku): bool {
                $payload = $row['payload'] ?? [];

                if (in_array((int) ($row['entity_id'] ?? 0), $relatedEntityIds, true)) {
                    return true;
                }

                if (($payload['sku'] ?? null) === $sku) {
                    return true;
                }

                return in_array($sku, $payload['related_product_skus'] ?? [], true);
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
            'store' => $this->storeView,
            'store_id' => $this->storeId,
            'product' => $this->productId,
            'type' => $this->productType,
        ], fn (string $value): bool => $value !== '');
    }

    private function canViewProduct(DomainPolicy $policy): bool
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
            'id' => 905,
            'name' => "{$this->role} product fixture",
            'email' => "{$this->role}-product@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }
}
