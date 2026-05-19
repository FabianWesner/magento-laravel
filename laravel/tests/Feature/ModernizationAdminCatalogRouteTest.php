<?php

namespace Tests\Feature;

use App\Livewire\AdminCatalogWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationAdminCatalogRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_catalog_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.admin.catalog-management'));

        $response
            ->assertOk()
            ->assertSee('Modernization Admin Catalog')
            ->assertSee(route('modernization.assets.admin-catalog'))
            ->assertSee('Admin catalog management workbench')
            ->assertSee('Admin catalog diagnostics')
            ->assertSee('AD-002')
            ->assertSee('AD-003')
            ->assertSee('AD-004')
            ->assertSee('Simple Shirt')
            ->assertSee('Configurable Hoodie');
    }

    public function test_admin_catalog_asset_route_returns_css(): void
    {
        $response = $this->get(route('modernization.assets.admin-catalog'));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8');
    }

    public function test_admin_catalog_livewire_sections_filters_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(AdminCatalogWorkbench::class)
            ->assertSee('Simple Shirt')
            ->assertSee('Configurable Hoodie')
            ->assertSee('2 products')
            ->assertSee('2 categories')
            ->assertSee('7 attribute rows')
            ->assertSee('2 media rows')
            ->assertSee('1 downloads')
            ->assertSee('1 problems')
            ->call('setSection', 'categories')
            ->assertSee('Women')
            ->assertSee('Gear')
            ->set('section', 'attributes')
            ->assertSee('Simple Shirt attribute set')
            ->assertSee('Configurable Hoodie configurable attribute')
            ->set('type', 'configurable_attribute')
            ->assertSee('Configurable Hoodie configurable attribute')
            ->assertDontSee('Simple Shirt attribute set')
            ->set('type', '')
            ->set('section', 'media')
            ->assertSee('catalog/product/simple-shirt/main.jpg')
            ->assertSee('catalog/product/configurable-hoodie/missing-swatch.jpg')
            ->set('status', 'missing_media')
            ->assertSee('Configurable Hoodie')
            ->assertDontSee('Simple Shirt front')
            ->set('status', '')
            ->set('section', 'downloads')
            ->assertSee('downloadable-size-guide')
            ->assertSee('customer_account_purchase')
            ->set('section', 'problems')
            ->assertSee('Configurable Hoodie')
            ->assertSee('Missing Media')
            ->set('storeView', 'de')
            ->set('section', 'products')
            ->assertSee('Einfaches Hemd')
            ->assertSee('Konfigurierbarer Hoodie')
            ->set('query', 'not-present')
            ->assertSee('There are no products facts matching this admin catalog scope.')
            ->call('clearFilters')
            ->assertSee('Simple Shirt')
            ->set('role', 'denied')
            ->assertSee('Permission denied for admin catalog role `denied`.');
    }

    public function test_admin_catalog_livewire_normalizes_invalid_public_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(AdminCatalogWorkbench::class)
            ->set('storeId', 'invalid-store')
            ->set('storeView', 'invalid-view')
            ->set('status', 'invalid-status')
            ->set('type', 'invalid-type')
            ->set('section', 'invalid-section')
            ->set('role', 'invalid-role')
            ->assertSet('storeId', '')
            ->assertSet('storeView', 'default')
            ->assertSet('status', '')
            ->assertSet('type', '')
            ->assertSet('section', 'products')
            ->assertSet('role', 'catalog')
            ->assertSee('Simple Shirt');
    }
}
