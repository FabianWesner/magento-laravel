<?php

namespace Tests\Feature;

use App\Livewire\ProductDetailWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationProductDetailRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_product_detail_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.storefront.product-detail'));

        $response
            ->assertOk()
            ->assertSee('Modernization Product Detail')
            ->assertSee(route('modernization.assets.product-detail'))
            ->assertSee('Product detail workbench')
            ->assertSee('Product detail')
            ->assertSee('SF-005')
            ->assertSee('Simple Shirt');
    }

    public function test_product_detail_livewire_sections_scope_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(ProductDetailWorkbench::class)
            ->assertSee('Simple Shirt')
            ->assertSee('2 product rows')
            ->set('productId', '3002')
            ->assertSee('Configurable Hoodie')
            ->assertSee('1 product rows')
            ->call('setSection', 'media')
            ->assertSee('Configurable Hoodie front')
            ->assertSee('Missing media')
            ->call('setSection', 'options')
            ->assertSee('Size')
            ->call('setSection', 'commerce')
            ->assertSee('Downloadable')
            ->assertSee('Download permission tracked')
            ->set('storeView', 'de')
            ->assertSee('Einfaches Hemd')
            ->assertDontSee('Configurable Hoodie')
            ->set('productType', 'downloadable')
            ->assertSee('There are no product detail facts for this scope.')
            ->call('clearFilters')
            ->assertSee('Simple Shirt')
            ->set('role', 'denied')
            ->assertSee('Permission denied for product role `denied`.');
    }
}
