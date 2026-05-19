<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminParityGrid extends Component
{
    public string $featureId = 'AD-011';

    public string $filter = 'all';

    public function selectFeature(string $featureId): void
    {
        if (! array_key_exists($featureId, $this->rows())) {
            return;
        }

        $this->featureId = $featureId;
    }

    public function setFilter(string $filter): void
    {
        if (! in_array($filter, ['all', 'edge', 'failure'], true)) {
            return;
        }

        $this->filter = $filter;
    }

    /**
     * @return array<string, array{label: string, fixture_id: string, legacy: string, coverage: list<string>}>
     */
    public function rows(): array
    {
        return [
            'AD-011' => [
                'label' => 'Cache, indexes, compiler',
                'fixture_id' => 'AD-CACHE-INDEX-001',
                'legacy' => 'Magento cache and index status baseline',
                'coverage' => ['cache flush', 'stale index', 'compiler retained decision', 'failure retry rollback'],
            ],
            'AD-016' => [
                'label' => 'Tax and currency',
                'fixture_id' => 'AD-TAX-CURRENCY-001',
                'legacy' => 'Magento tax and currency baseline',
                'coverage' => ['tax classes', 'tax rates', 'currency rates', 'permission denied import'],
            ],
        ];
    }

    public function render(): View
    {
        return view('livewire.admin-parity-grid', [
            'rows' => $this->rows(),
            'selectedRow' => $this->rows()[$this->featureId],
        ]);
    }
}
