<?php

namespace Tests\Feature;

use App\Livewire\SystemConfigWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationSystemConfigRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_system_config_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.admin.system-config'));

        $response
            ->assertOk()
            ->assertSee('Modernization Admin System Config')
            ->assertSee(route('modernization.assets.system-config'))
            ->assertSee('System config workbench')
            ->assertSee('System configuration, Store scope')
            ->assertSee('SF-012, AD-010, CB-011, CB-013, SF-001, SF-002, AD-017')
            ->assertSee('Base URL')
            ->assertSee('Gateway Token');

        $this->get(route('modernization.assets.system-config'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8');
    }

    public function test_admin_system_config_livewire_filters_sections_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(SystemConfigWorkbench::class)
            ->assertSee('2 scope rows')
            ->assertSee('8 config rows')
            ->assertSee('5 validation rows')
            ->assertSee('4 safety rows')
            ->assertSee('Base URL')
            ->assertSee('Gateway Token')
            ->assertSee('Invalid List Mode')
            ->assertSee('Source model plan: grid / list')
            ->call('setSection', 'scopes')
            ->assertSee('Default Store View')
            ->assertSee('German Store View')
            ->call('setSection', 'validation')
            ->assertSee('gallery')
            ->assertSee('The selected system configuration source model value [gallery] is invalid.')
            ->call('setSection', 'secrets')
            ->assertSee('Gateway Token')
            ->assertSee('Secure Base URL')
            ->assertSee('Invalidated On Inherit')
            ->set('storeView', 'de')
            ->assertSee('1 scope rows')
            ->assertSee('3 config rows')
            ->assertSee('3 validation rows')
            ->assertSee('1 safety rows')
            ->call('setSection', 'config')
            ->assertSee('Locale')
            ->assertSee('Base Currency')
            ->assertSee('Inherited List Mode')
            ->set('state', 'inherited')
            ->assertSee('2 config rows')
            ->assertSee('Base Currency')
            ->assertSee('Inherited List Mode')
            ->assertDontSee('Locale')
            ->set('group', 'catalog')
            ->assertSee('1 config rows')
            ->assertSee('Inherited List Mode')
            ->assertDontSee('Base Currency')
            ->set('query', 'not-present')
            ->assertSee('There are no config facts matching this system configuration scope.')
            ->call('clearFilters')
            ->assertSee('Base URL')
            ->set('role', 'denied')
            ->assertSee('Permission denied for system config role `denied`.')
            ->assertDontSee('Source model plan');
    }

    public function test_admin_system_config_livewire_normalizes_invalid_public_filter_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(SystemConfigWorkbench::class)
            ->set('storeId', 'bad-store')
            ->set('storeView', 'bad-view')
            ->set('state', 'bad-state')
            ->set('group', 'bad-group')
            ->set('section', 'bad-section')
            ->assertSet('storeId', '')
            ->assertSet('storeView', '')
            ->assertSet('state', '')
            ->assertSet('group', '')
            ->assertSet('section', 'config')
            ->assertSee('8 config rows')
            ->assertDontSee('Store: bad-view');
    }
}
