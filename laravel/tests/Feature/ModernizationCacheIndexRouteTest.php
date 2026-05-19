<?php

namespace Tests\Feature;

use App\Livewire\CacheIndexWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationCacheIndexRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_cache_index_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.admin.cache-index'));

        $response
            ->assertOk()
            ->assertSee('Modernization Admin Cache Index')
            ->assertSee(route('modernization.assets.cache-index'))
            ->assertSee('Cache index workbench')
            ->assertSee('Cache and compiler operations, Index management')
            ->assertSee('AD-011, CB-013, CJ-016, CB-012, CJ-021')
            ->assertSee('Configuration')
            ->assertSee('Blocks HTML output');

        $this->get(route('modernization.assets.cache-index'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8');
    }

    public function test_admin_cache_index_livewire_filters_sections_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(CacheIndexWorkbench::class)
            ->assertSee('6 cache rows')
            ->assertSee('Configuration')
            ->assertSee('Blocks HTML output')
            ->call('setSection', 'index')
            ->assertSee('6 index rows')
            ->assertSee('Product Prices')
            ->assertSee('Catalog URL Rewrites')
            ->call('setSection', 'cron')
            ->assertSee('8 cron rows')
            ->assertSee('core_clean_cache')
            ->assertSee('catalog_product_index_price_reindex_all')
            ->set('type', 'cron')
            ->assertSee('8 cron rows')
            ->assertSee('core_clean_cache')
            ->set('type', '')
            ->call('setSection', 'locks')
            ->assertSee('6 lock rows')
            ->assertSee('lock_wait_timeout')
            ->assertSee('indexer:catalog_url:9001')
            ->set('storeView', 'de')
            ->assertSee('2 lock rows')
            ->assertSee('Uebersetzungen')
            ->assertSee('Katalogsuche')
            ->set('status', 'reindex_required')
            ->assertSee('Katalogsuche')
            ->assertDontSee('Uebersetzungen')
            ->set('status', '')
            ->call('setSection', 'cache')
            ->set('storeView', '')
            ->set('type', 'compiler')
            ->assertSee('Compiler controls')
            ->set('query', 'not-present')
            ->assertSee('There are no cache facts matching this cache index scope.')
            ->call('clearFilters')
            ->assertSee('Configuration')
            ->set('role', 'denied')
            ->assertSee('Permission denied for cache index role `denied`.')
            ->assertDontSee('Blocks HTML output');
    }

    public function test_admin_cache_index_livewire_normalizes_invalid_public_filter_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(CacheIndexWorkbench::class)
            ->set('storeId', 'bad-store')
            ->set('storeView', 'bad-view')
            ->set('status', 'bad-status')
            ->set('type', 'bad-type')
            ->set('section', 'bad-section')
            ->assertSet('storeId', '')
            ->assertSet('storeView', '')
            ->assertSet('status', '')
            ->assertSet('type', '')
            ->assertSet('section', 'cache')
            ->assertSee('6 cache rows')
            ->assertDontSee('Store: bad-view');
    }
}
