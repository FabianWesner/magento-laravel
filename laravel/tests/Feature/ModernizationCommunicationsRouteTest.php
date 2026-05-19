<?php

namespace Tests\Feature;

use App\Livewire\CommunicationsWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationCommunicationsRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_storefront_communications_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.storefront.communications'));

        $response
            ->assertOk()
            ->assertSee('Modernization Storefront Communications')
            ->assertSee(route('modernization.assets.communications'))
            ->assertSee('Communications workbench')
            ->assertSee('Newsletter, Contact and send to friend')
            ->assertSee('SF-016, AD-015, CJ-022, AD-017')
            ->assertSee('maria.sommer@example.test');

        $this->get(route('modernization.assets.communications'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8');
    }

    public function test_storefront_communications_livewire_filters_sections_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(CommunicationsWorkbench::class)
            ->assertSee('6 newsletter rows')
            ->assertSee('maria.sommer@example.test')
            ->assertSee('Newsletter plan')
            ->call('setSection', 'contact')
            ->assertSee('10 contact rows')
            ->assertSee('Contact Form')
            ->assertSee('support@example.test')
            ->call('setSection', 'alerts')
            ->assertSee('6 alert rows')
            ->assertSee('Simple Shirt')
            ->assertSee('alex.friend@example.test')
            ->call('setSection', 'queue')
            ->assertSee('13 queue rows')
            ->assertSee('domain-communications')
            ->assertSee('mail_transport_failed')
            ->set('storeView', 'de')
            ->assertSee('lena.keller@example.test')
            ->assertSee('support-de@example.test')
            ->set('status', 'failed')
            ->assertSee('mail_transport_failed')
            ->assertDontSee('maria.sommer@example.test')
            ->set('status', '')
            ->set('channel', 'product_alert')
            ->assertSee('Einfaches Hemd')
            ->assertDontSee('mia.freundin@example.test')
            ->set('query', 'not-present')
            ->assertSee('There are no queue facts matching this communications scope.')
            ->call('clearFilters')
            ->assertSee('maria.sommer@example.test')
            ->set('role', 'denied')
            ->assertSee('Permission denied for communications role `denied`.')
            ->assertDontSee('Newsletter plan');
    }

    public function test_storefront_communications_livewire_normalizes_invalid_public_filter_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(CommunicationsWorkbench::class)
            ->set('storeId', 'bad-store')
            ->set('storeView', 'bad-view')
            ->set('status', 'bad-status')
            ->set('channel', 'bad-channel')
            ->set('section', 'bad-section')
            ->assertSet('storeId', '')
            ->assertSet('storeView', '')
            ->assertSet('status', '')
            ->assertSet('channel', '')
            ->assertSet('section', 'newsletter')
            ->assertSee('6 newsletter rows')
            ->assertDontSee('Store: bad-view');
    }
}
