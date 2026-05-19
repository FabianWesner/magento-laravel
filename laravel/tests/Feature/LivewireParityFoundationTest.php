<?php

namespace Tests\Feature;

use App\Livewire\AdminParityGrid;
use App\Livewire\StorefrontParityPanel;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireParityFoundationTest extends TestCase
{
    public function test_storefront_livewire_component_tracks_cart_checkout_and_multishipping_state(): void
    {
        Livewire::test(StorefrontParityPanel::class)
            ->assertSet('featureId', 'SF-007')
            ->assertSee('SF-007 Cart')
            ->assertSee('SF-CART-001')
            ->assertSee('invalid input')
            ->call('selectFeature', 'SF-008')
            ->assertSet('featureId', 'SF-008')
            ->assertSee('Checkout')
            ->assertSee('SF-CHECKOUT-001')
            ->call('selectFeature', 'SF-009')
            ->assertSet('featureId', 'SF-009')
            ->assertSee('Multishipping checkout')
            ->assertSee('SF-MULTISHIP-001');
    }

    public function test_admin_livewire_grid_tracks_cache_index_compiler_tax_and_currency_state(): void
    {
        Livewire::test(AdminParityGrid::class)
            ->assertSet('featureId', 'AD-011')
            ->assertSee('AD-011 Cache, indexes, compiler')
            ->assertSee('AD-CACHE-INDEX-001')
            ->assertSee('stale index')
            ->call('setFilter', 'failure')
            ->assertSet('filter', 'failure')
            ->assertSee('Filter: Failure')
            ->call('selectFeature', 'AD-016')
            ->assertSet('featureId', 'AD-016')
            ->assertSee('Tax and currency')
            ->assertSee('AD-TAX-CURRENCY-001')
            ->assertSee('permission denied import');
    }
}
