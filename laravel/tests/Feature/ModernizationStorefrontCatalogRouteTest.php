<?php

namespace Tests\Feature;

use App\Livewire\StorefrontCatalogWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationStorefrontCatalogRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_storefront_catalog_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.storefront.catalog'));

        $response
            ->assertOk()
            ->assertSee('Modernization Storefront Catalog')
            ->assertSee(route('modernization.assets.storefront-catalog'))
            ->assertSee('Catalog browse workbench')
            ->assertSee('Storefront catalog')
            ->assertSee('Catalog browsing')
            ->assertSee('SF-002, AD-002')
            ->assertSee('Simple Shirt')
            ->assertSee('Configurable Hoodie');
    }

    public function test_storefront_catalog_livewire_filters_modes_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(StorefrontCatalogWorkbench::class)
            ->assertSee('Simple Shirt')
            ->assertSee('Configurable Hoodie')
            ->assertSee('2 visible')
            ->set('storeView', 'de')
            ->assertSee('Einfaches Hemd')
            ->assertDontSee('Configurable Hoodie')
            ->set('storeView', '')
            ->set('category', 'Gear')
            ->assertSee('Configurable Hoodie')
            ->assertDontSee('Simple Shirt')
            ->set('category', '')
            ->set('type', 'simple')
            ->assertSee('Simple Shirt')
            ->assertDontSee('Configurable Hoodie')
            ->set('type', '')
            ->set('swatch', 'black')
            ->assertSee('Configurable Hoodie')
            ->call('setMode', 'list')
            ->assertSet('mode', 'list')
            ->set('query', 'no-matching-product')
            ->assertSee('There are no products matching the selection.')
            ->call('clearFilters')
            ->assertSee('Simple Shirt')
            ->set('role', 'denied')
            ->assertSee('Permission denied for catalog role `denied`.');
    }
}
