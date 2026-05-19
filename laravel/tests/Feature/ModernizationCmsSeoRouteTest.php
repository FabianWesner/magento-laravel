<?php

namespace Tests\Feature;

use App\Livewire\CmsSeoWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationCmsSeoRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_storefront_cms_seo_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.storefront.cms-seo'));

        $response
            ->assertOk()
            ->assertSee('Modernization Storefront CMS SEO')
            ->assertSee(route('modernization.assets.cms-seo'))
            ->assertSee('CMS SEO workbench')
            ->assertSee('CMS page, CMS block, Widget output, Sitemap and RSS, URL rewrite')
            ->assertSee('SF-001, SF-013, AD-007, SF-014, CJ-025, CB-012, SF-002')
            ->assertSee('About Us')
            ->assertSee('Spring Sale Legacy');

        $this->get(route('modernization.assets.cms-seo'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8');
    }

    public function test_storefront_cms_seo_livewire_filters_sections_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(CmsSeoWorkbench::class)
            ->assertSee('6 page rows')
            ->assertSee('About Us')
            ->assertSee('No Route')
            ->call('setSection', 'blocks')
            ->assertSee('4 block rows')
            ->assertSee('Footer Links')
            ->assertSee('home-page-hero')
            ->call('setSection', 'widgets')
            ->assertSee('4 widget rows')
            ->assertSee('New Products Rail')
            ->assertSee('Retired Promo Block Widget')
            ->call('setSection', 'seo')
            ->assertSee('10 SEO rows')
            ->assertSee('sitemap.xml')
            ->assertSee('/rss/catalog/new')
            ->assertSee('company')
            ->set('storeView', 'de')
            ->assertSee('de/sitemap.xml')
            ->set('contentState', 'redirect')
            ->assertSee('de/unternehmen')
            ->assertDontSee('de/sitemap.xml')
            ->set('contentState', '')
            ->set('freshness', 'stale')
            ->assertSee('de/rss/catalog/special.xml')
            ->assertSee('Stale')
            ->call('setSection', 'pages')
            ->set('freshness', '')
            ->assertSee('Ueber Uns')
            ->set('query', 'not-present')
            ->assertSee('There are no pages facts matching this CMS SEO scope.')
            ->call('clearFilters')
            ->assertSee('About Us')
            ->set('role', 'denied')
            ->assertSee('Permission denied for CMS SEO role `denied`.')
            ->assertDontSee('Footer Links');
    }

    public function test_storefront_cms_seo_livewire_normalizes_invalid_public_filter_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(CmsSeoWorkbench::class)
            ->set('storeId', 'bad-store')
            ->set('storeView', 'bad-view')
            ->set('contentState', 'bad-state')
            ->set('freshness', 'bad-freshness')
            ->set('section', 'bad-section')
            ->assertSet('storeId', '')
            ->assertSet('storeView', '')
            ->assertSet('contentState', '')
            ->assertSet('freshness', '')
            ->assertSet('section', 'pages')
            ->assertSee('6 page rows')
            ->assertDontSee('Store: bad-view');
    }
}
