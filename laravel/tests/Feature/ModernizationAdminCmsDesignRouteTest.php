<?php

namespace Tests\Feature;

use App\Livewire\AdminCmsDesignWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationAdminCmsDesignRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_cms_design_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.admin.cms-design'));

        $response
            ->assertOk()
            ->assertSee('Modernization Admin CMS Design')
            ->assertSee(route('modernization.assets.admin-cms-design'))
            ->assertSee('Admin CMS design workbench')
            ->assertSee('Admin CMS and design diagnostics')
            ->assertSee('AD-009')
            ->assertSee('SF-002')
            ->assertSee('About Us')
            ->assertSee('home-page-hero');
    }

    public function test_admin_cms_design_asset_route_returns_css(): void
    {
        $response = $this->get(route('modernization.assets.admin-cms-design'));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8');
    }

    public function test_admin_cms_design_livewire_sections_filters_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(AdminCmsDesignWorkbench::class)
            ->assertSee('6 pages')
            ->assertSee('4 blocks')
            ->assertSee('4 widgets')
            ->assertSee('4 design/cache rows')
            ->assertSee('4 rewrites')
            ->assertSee('9 problems')
            ->assertSee('About Us')
            ->assertSee('Spring Sale Legacy')
            ->call('setSection', 'blocks')
            ->assertSee('Footer Links')
            ->assertSee('home-page-hero')
            ->call('setSection', 'widgets')
            ->assertSee('New Products Rail')
            ->assertSee('Retired Promo Block Widget')
            ->set('type', 'cms_widget_block')
            ->assertSee('Retired Promo Block Widget')
            ->assertDontSee('New Products Rail')
            ->set('type', '')
            ->call('setSection', 'design')
            ->assertSee('Default Store View')
            ->assertSee('German Store View')
            ->assertSee('Blocks HTML output')
            ->set('status', 'stale')
            ->assertSee('cms_block_save_invalidated_block_html')
            ->assertDontSee('Default Store View')
            ->set('status', '')
            ->call('setSection', 'rewrites')
            ->assertSee('company')
            ->assertSee('de/unternehmen')
            ->set('status', 'redirect')
            ->assertSee('company')
            ->set('status', '')
            ->call('setSection', 'problems')
            ->assertSee('Spring Sale Legacy')
            ->assertSee('404 Not Found')
            ->assertSee('Blocks HTML output')
            ->set('storeView', 'de')
            ->set('section', 'pages')
            ->assertSee('Ueber Uns')
            ->assertDontSee('About Us')
            ->set('query', 'not-present')
            ->assertSee('There are no pages facts matching this admin CMS design scope.')
            ->call('clearFilters')
            ->assertSee('About Us')
            ->set('role', 'denied')
            ->assertSee('Permission denied for admin CMS design role `denied`.')
            ->assertDontSee('Home Page Hero');
    }

    public function test_admin_cms_design_livewire_normalizes_invalid_public_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(AdminCmsDesignWorkbench::class)
            ->set('storeId', 'invalid-store')
            ->set('storeView', 'invalid-view')
            ->set('status', 'invalid-status')
            ->set('type', 'invalid-type')
            ->set('section', 'invalid-section')
            ->set('role', 'invalid-role')
            ->assertSet('storeId', '')
            ->assertSet('storeView', '')
            ->assertSet('status', '')
            ->assertSet('type', '')
            ->assertSet('section', 'pages')
            ->assertSet('role', 'catalog')
            ->assertSee('About Us');
    }
}
