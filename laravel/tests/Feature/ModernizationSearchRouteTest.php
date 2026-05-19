<?php

namespace Tests\Feature;

use App\Livewire\SearchWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationSearchRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_storefront_search_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.storefront.search'));

        $response
            ->assertOk()
            ->assertSee('Modernization Storefront Search')
            ->assertSee(route('modernization.assets.search'))
            ->assertSee('Search workbench')
            ->assertSee('Search and RSS')
            ->assertSee('SF-006, SF-014, CJ-019')
            ->assertSee('shirt')
            ->assertSee('hoodie under 60');
    }

    public function test_storefront_search_livewire_filters_sections_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(SearchWorkbench::class)
            ->assertSee('shirt')
            ->assertSee('3 search rows')
            ->assertSee('Simple Shirt')
            ->set('query', 'hoodie')
            ->assertSee('hoodie under 60')
            ->assertSee('Configurable Hoodie')
            ->set('query', '')
            ->set('queryType', 'advanced')
            ->assertSee('hoodie under 60')
            ->assertDontSee('legacy jacket')
            ->set('queryType', '')
            ->set('redirectState', 'redirected')
            ->assertSee('legacy jacket')
            ->assertSee('/gear/configurable-hoodie.html')
            ->set('redirectState', '')
            ->set('indexState', 'stale')
            ->assertSee('Index stale')
            ->assertSee('legacy jacket')
            ->call('setSection', 'seo')
            ->assertSee('/catalogsearch/result/?q=legacy+jacket')
            ->assertSee('/rss/catalog/notifystock/?q=legacy+jacket')
            ->call('setSection', 'results')
            ->set('indexState', '')
            ->set('storeView', 'de')
            ->assertSee('hemd')
            ->assertSee('Einfaches Hemd')
            ->set('resultState', 'no_results')
            ->assertSee('winterjacke')
            ->assertSee('No product rows are attached to this search snapshot.')
            ->set('query', 'not-present')
            ->assertSee('There are no search facts matching the selection.')
            ->call('clearFilters')
            ->assertSee('shirt')
            ->set('role', 'denied')
            ->assertSee('Permission denied for search role `denied`.')
            ->assertDontSee('Simple Shirt');
    }

    public function test_storefront_search_scopes_aggregate_product_rows_and_normalizes_public_section_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(SearchWorkbench::class)
            ->set('storeView', '')
            ->assertSee('6 search rows')
            ->assertSee('1 product rows')
            ->assertSee('Simple Shirt')
            ->assertDontSee('Einfaches Hemd')
            ->set('query', 'hemd')
            ->assertSee('1 search rows')
            ->assertSee('1 product rows')
            ->assertSee('Einfaches Hemd')
            ->set('query', '')
            ->set('section', 'unexpected-client-state')
            ->assertSee('Simple Shirt')
            ->assertDontSee('Search index snapshot is current.');
    }
}
