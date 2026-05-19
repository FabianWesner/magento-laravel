<?php

namespace Tests\Feature;

use App\Livewire\TaxCurrencyWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationTaxCurrencyRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_tax_currency_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.admin.tax-currency'));

        $response
            ->assertOk()
            ->assertSee('Modernization Admin Tax Currency')
            ->assertSee(route('modernization.assets.tax-currency'))
            ->assertSee('Tax currency workbench')
            ->assertSee('Tax rates and rules, Currency rates and symbols')
            ->assertSee('AD-016, CB-006, CJ-020, CJ-002')
            ->assertSee('Tax classes')
            ->assertSee('US-CA 8.25');

        $this->get(route('modernization.assets.tax-currency'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8');
    }

    public function test_admin_tax_currency_livewire_filters_sections_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(TaxCurrencyWorkbench::class)
            ->assertSee('8 tax rows')
            ->assertSee('7 currency rows')
            ->assertSee('3 job rows')
            ->assertSee('3 problem rows')
            ->assertSee('Tax classes')
            ->assertSee('US-CA 8.25')
            ->call('setSection', 'currency')
            ->assertSee('USD to EUR')
            ->assertSee('EUR to USD Stale')
            ->call('setSection', 'jobs')
            ->assertSee('aggregate_sales_report_tax_data')
            ->assertSee('currency_rates_update')
            ->call('setSection', 'problems')
            ->assertSee('Invalid Percent Rate')
            ->assertSee('EUR to USD Stale')
            ->assertSee('ECB Import Failure')
            ->set('storeView', 'de')
            ->call('setSection', 'tax')
            ->assertSee('2 tax rows')
            ->assertSee('DE VAT Standard')
            ->assertSee('EU VAT Rule')
            ->call('setSection', 'currency')
            ->assertSee('2 currency rows')
            ->assertSee('DE Display Currency')
            ->assertSee('DE Currency Symbol Override')
            ->set('storeView', '')
            ->set('status', 'stale')
            ->assertSee('1 currency rows')
            ->assertSee('EUR to USD Stale')
            ->set('query', 'not-present')
            ->assertSee('There are no currency facts matching this tax currency scope.')
            ->call('clearFilters')
            ->assertSee('Tax classes')
            ->set('role', 'denied')
            ->assertSee('Permission denied for tax currency role `denied`.')
            ->assertDontSee('US-CA 8.25');
    }

    public function test_admin_tax_currency_livewire_normalizes_invalid_public_filter_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(TaxCurrencyWorkbench::class)
            ->set('storeId', 'bad-store')
            ->set('storeView', 'bad-view')
            ->set('status', 'bad-status')
            ->set('type', 'bad-type')
            ->set('section', 'bad-section')
            ->assertSet('storeId', '')
            ->assertSet('storeView', '')
            ->assertSet('status', '')
            ->assertSet('type', '')
            ->assertSet('section', 'tax')
            ->assertSee('8 tax rows')
            ->assertDontSee('Store: bad-view');
    }
}
