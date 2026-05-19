<?php

namespace Tests\Feature;

use App\Livewire\ImportExportWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationImportExportRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_import_export_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.admin.import-export'));

        $response
            ->assertOk()
            ->assertSee('Modernization Admin Import Export')
            ->assertSee(route('modernization.assets.import-export'))
            ->assertSee('Import export workbench')
            ->assertSee('Import export, Dataflow profiles')
            ->assertSee('AD-013')
            ->assertSee('Catalog Product Import')
            ->assertSee('Customer Import Validation');

        $this->get(route('modernization.assets.import-export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8');
    }

    public function test_admin_import_export_livewire_filters_sections_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(ImportExportWorkbench::class)
            ->assertSee('7 import rows')
            ->assertSee('Catalog Product Import')
            ->assertSee('CSV / passed')
            ->call('setSection', 'exports')
            ->assertSee('5 export rows')
            ->assertSee('Catalog Product Export')
            ->assertSee('Customer Export Profile')
            ->call('setSection', 'profiles')
            ->assertSee('6 profile rows')
            ->assertSee('Stock Import Dry Run')
            ->assertSee('cataloginventory/convert_adapter_stock')
            ->call('setSection', 'files')
            ->assertSee('11 file rows')
            ->assertSee('customer-import-errors.csv')
            ->assertSee('de-products.csv')
            ->set('storeView', 'de')
            ->assertSee('2 import rows')
            ->assertSee('2 export rows')
            ->assertSee('2 profile rows')
            ->assertSee('4 file rows')
            ->set('status', 'blocked')
            ->assertSee('DE Import Customers Profile')
            ->assertSee('de-customer-import-errors.csv')
            ->assertDontSee('DE Product Export')
            ->set('status', '')
            ->set('entity', 'catalog_product')
            ->assertSee('DE Catalog Export Profile')
            ->assertDontSee('DE Import Customers Profile')
            ->set('query', 'not-present')
            ->assertSee('There are no files facts matching this import export scope.')
            ->call('clearFilters')
            ->assertSee('Catalog Product Import')
            ->set('role', 'denied')
            ->assertSee('Permission denied for import export role `denied`.')
            ->assertDontSee('Validation plan');
    }

    public function test_admin_import_export_livewire_normalizes_invalid_public_filter_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(ImportExportWorkbench::class)
            ->set('storeId', 'bad-store')
            ->set('storeView', 'bad-view')
            ->set('status', 'bad-status')
            ->set('operation', 'bad-operation')
            ->set('entity', 'bad-entity')
            ->set('section', 'bad-section')
            ->assertSet('storeId', '')
            ->assertSet('storeView', '')
            ->assertSet('status', '')
            ->assertSet('operation', '')
            ->assertSet('entity', '')
            ->assertSet('section', 'imports')
            ->assertSee('7 import rows')
            ->assertDontSee('Store: bad-view');
    }
}
