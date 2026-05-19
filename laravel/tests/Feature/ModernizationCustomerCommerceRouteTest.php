<?php

namespace Tests\Feature;

use App\Livewire\CustomerCommerceWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationCustomerCommerceRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_commerce_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.customer.commerce'));

        $response
            ->assertOk()
            ->assertSee('Modernization Customer Commerce')
            ->assertSee(route('modernization.assets.customer-commerce'))
            ->assertSee('Customer commerce workbench')
            ->assertSee('Wishlist, Compare products, Reviews, Tags')
            ->assertSee('SF-011')
            ->assertSee('simple-shirt');

        $this->get(route('modernization.assets.customer-commerce'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8');
    }

    public function test_customer_commerce_livewire_filters_sections_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(CustomerCommerceWorkbench::class)
            ->assertSee('3 wishlist rows')
            ->assertSee('simple-shirt')
            ->assertSee('wl-default-maria-001')
            ->call('setSection', 'compare')
            ->assertSee('3 compare rows')
            ->assertSee('Color')
            ->assertSee('guest_session_expired')
            ->call('setSection', 'reviews')
            ->assertSee('3 review rows')
            ->assertSee('Reliable shirt')
            ->assertSee('Quality')
            ->call('setSection', 'tags')
            ->assertSee('3 tag rows')
            ->assertSee('summer')
            ->set('storeView', 'de')
            ->assertSee('Customer 5002')
            ->assertSee('sommer')
            ->call('setSection', 'wishlist')
            ->assertSee('Sichere geteilte Wunschlisten-Fixture.')
            ->set('productSku', 'configurable-hoodie')
            ->assertSee('There are no wishlist facts matching this customer commerce scope.')
            ->call('clearFilters')
            ->assertSee('simple-shirt')
            ->set('status', 'pending')
            ->call('setSection', 'reviews')
            ->assertSee('Awaiting moderation')
            ->set('role', 'denied')
            ->assertSee('Permission denied for customer commerce role `denied`.')
            ->assertDontSee('Reliable shirt');
    }

    public function test_customer_commerce_livewire_normalizes_invalid_public_filter_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(CustomerCommerceWorkbench::class)
            ->set('storeId', 'bad-store')
            ->set('storeView', 'bad-view')
            ->set('status', 'bad-status')
            ->set('section', 'bad-section')
            ->assertSet('storeId', '')
            ->assertSet('storeView', '')
            ->assertSet('status', '')
            ->assertSet('section', 'wishlist')
            ->assertSee('3 wishlist rows')
            ->assertDontSee('Store: bad-view');
    }
}
