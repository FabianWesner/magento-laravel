<?php

namespace Tests\Feature;

use App\Livewire\AdminStoreOperationsWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationAdminStoreOperationsRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_store_operations_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.admin.store-operations'));

        $response
            ->assertOk()
            ->assertSee('Modernization Admin Store Operations')
            ->assertSee(route('modernization.assets.admin-store-operations'))
            ->assertSee('Admin store operations workbench')
            ->assertSee('Admin store operations diagnostics')
            ->assertSee('AD-017')
            ->assertSee('Default Store View')
            ->assertSee('German Store View')
            ->assertSee('Backups')
            ->assertSee('Email templates');
    }

    public function test_admin_store_operations_asset_route_returns_css(): void
    {
        $response = $this->get(route('modernization.assets.admin-store-operations'));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8');
    }

    public function test_admin_store_operations_livewire_sections_filters_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(AdminStoreOperationsWorkbench::class)
            ->assertSee('2 stores')
            ->assertSee('3 backups')
            ->assertSee('2 system rows')
            ->assertSee('4 templates')
            ->assertSee('4 rewrites')
            ->assertSee('4 sitemap rows')
            ->assertSee('5 problems')
            ->assertSee('Default Store View')
            ->assertSee('German Store View')
            ->call('setSection', 'backups')
            ->assertSee('Nightly Database Backup')
            ->assertSee('Media Backup Failure')
            ->set('status', 'failed')
            ->assertSee('Media Backup Failure')
            ->assertDontSee('Nightly Database Backup')
            ->set('status', '')
            ->call('setSection', 'system')
            ->assertSee('Runtime Compatibility Snapshot')
            ->assertSee('Project Overlay Readiness')
            ->set('status', 'warning')
            ->assertSee('Project Overlay Readiness')
            ->assertDontSee('Runtime Compatibility Snapshot')
            ->set('status', '')
            ->call('setSection', 'templates')
            ->assertSee('Order Confirmation Override')
            ->assertSee('Sitemap Generation Warning')
            ->assertSee('Contact Template Missing Subject')
            ->set('storeView', 'de')
            ->assertSee('Kundenkonto Willkommen')
            ->assertDontSee('Order Confirmation Override')
            ->set('storeView', '')
            ->call('setSection', 'rewrites')
            ->set('status', 'redirect')
            ->assertSee('company')
            ->assertSee('de/unternehmen')
            ->set('status', '')
            ->call('setSection', 'sitemaps')
            ->set('status', 'stale')
            ->assertSee('rss/catalog/special.xml')
            ->assertSee('de/rss/catalog/special.xml')
            ->set('status', '')
            ->call('setSection', 'problems')
            ->assertSee('Media Backup Failure')
            ->assertSee('Project Overlay Readiness')
            ->assertSee('Contact Template Missing Subject')
            ->assertSee('rss/catalog/special.xml')
            ->set('query', 'not-present')
            ->assertSee('There are no problems facts matching this admin store operations scope.')
            ->call('clearFilters')
            ->assertSee('Default Store View')
            ->set('role', 'denied')
            ->assertSee('Permission denied for admin store operations role `denied`.')
            ->assertDontSee('Media Backup Failure');
    }

    public function test_admin_store_operations_livewire_normalizes_invalid_public_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(AdminStoreOperationsWorkbench::class)
            ->set('storeId', 'bad-store')
            ->set('storeView', 'bad-view')
            ->set('status', 'bad-status')
            ->set('type', 'bad-type')
            ->set('section', 'bad-section')
            ->set('role', 'bad-role')
            ->assertSet('storeId', '')
            ->assertSet('storeView', '')
            ->assertSet('status', '')
            ->assertSet('type', '')
            ->assertSet('section', 'stores')
            ->assertSet('role', 'catalog')
            ->assertSee('2 stores')
            ->assertDontSee('Store: bad-view');
    }
}
