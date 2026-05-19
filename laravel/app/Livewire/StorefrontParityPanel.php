<?php

namespace App\Livewire;

use App\Modernization\Commerce\CommerceCalculator;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StorefrontParityPanel extends Component
{
    public string $featureId = 'SF-007';

    /**
     * @var array<string, float>
     */
    public array $totals = [];

    /**
     * @var list<string>
     */
    public array $edgeStates = [];

    public function mount(): void
    {
        $this->refreshSnapshot();
    }

    public function selectFeature(string $featureId): void
    {
        if (! array_key_exists($featureId, $this->features())) {
            return;
        }

        $this->featureId = $featureId;
        $this->refreshSnapshot();
    }

    /**
     * @return array<string, array{label: string, fixture_id: string, legacy: string, state: string}>
     */
    public function features(): array
    {
        return [
            'SF-007' => [
                'label' => 'Cart',
                'fixture_id' => 'SF-CART-001',
                'legacy' => 'Magento cart add/update/remove baseline',
                'state' => 'coupon, tax estimate, persistent cart',
            ],
            'SF-008' => [
                'label' => 'Checkout',
                'fixture_id' => 'SF-CHECKOUT-001',
                'legacy' => 'Magento onepage checkout baseline',
                'state' => 'guest, registered, failure, success',
            ],
            'SF-009' => [
                'label' => 'Multishipping checkout',
                'fixture_id' => 'SF-MULTISHIP-001',
                'legacy' => 'Magento multishipping baseline',
                'state' => 'multiple addresses and shipping methods',
            ],
        ];
    }

    public function render(): View
    {
        return view('livewire.storefront-parity-panel', [
            'features' => $this->features(),
            'selectedFeature' => $this->features()[$this->featureId],
        ]);
    }

    private function refreshSnapshot(): void
    {
        $snapshot = app(CommerceCalculator::class)->snapshot('cart', [
            'legacy result' => ['Magento' => $this->features()[$this->featureId]['legacy']],
            'items' => [
                ['sku' => strtolower($this->featureId), 'price' => 40, 'qty' => 2],
            ],
            'coupon_discount' => 5,
            'tax_rate' => 0.1,
            'shipping' => 9,
            'edge' => ['invalid input', 'permission denied', 'retry', 'rollback recovery'],
        ]);

        $this->totals = $snapshot['payload']['totals'];
        $this->edgeStates = $snapshot['payload']['edge'];
    }
}
