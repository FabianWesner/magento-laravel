<?php

namespace Tests\Feature;

use App\Livewire\StorefrontCartWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationStorefrontCartRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_storefront_cart_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.storefront.cart'));

        $response
            ->assertOk()
            ->assertSee('Modernization Storefront Cart')
            ->assertSee(route('modernization.assets.storefront-cart'))
            ->assertSee('Cart diagnostics workbench')
            ->assertSee('Storefront cart diagnostics')
            ->assertSee('Quote lifecycle')
            ->assertSee('SF-007')
            ->assertSee('Guest active quote')
            ->assertSee('Customer persistent quote');
    }

    public function test_storefront_cart_css_asset_route_is_available(): void
    {
        $response = $this->get(route('modernization.assets.storefront-cart'));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8');
    }

    public function test_storefront_cart_livewire_filters_sections_store_scope_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(StorefrontCartWorkbench::class)
            ->assertSee('Guest active quote')
            ->assertSee('Customer persistent quote')
            ->assertSee('5 quotes')
            ->assertSee('9 problems')
            ->set('status', 'expired')
            ->assertSee('Expired guest quote')
            ->assertDontSee('Guest active quote')
            ->call('setSection', 'items')
            ->set('status', 'invalid_quantity')
            ->assertSee('Bundle Quantity Guard')
            ->assertDontSee('Simple Shirt')
            ->call('setSection', 'shipping')
            ->set('status', 'unavailable')
            ->assertSee('Missing destination table rate')
            ->assertSee('DE Missing Destination Rate')
            ->set('storeView', 'de')
            ->assertSee('DE Missing Destination Rate')
            ->assertDontSee('Missing destination table rate')
            ->set('status', '')
            ->call('setSection', 'totals')
            ->assertSee('DE active cart totals')
            ->assertSee('EUR')
            ->call('setSection', 'problems')
            ->assertSee('DE stale cart totals')
            ->set('query', 'not-present')
            ->assertSee('There are no problems facts matching this cart scope.')
            ->call('clearFilters')
            ->assertSee('Guest active quote')
            ->set('role', 'denied')
            ->assertSee('Permission denied for storefront cart role `denied`.');
    }

    public function test_storefront_cart_livewire_normalizes_invalid_public_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(StorefrontCartWorkbench::class)
            ->set('storeId', '9999')
            ->set('storeView', 'fr')
            ->set('status', 'mutating')
            ->set('type', 'checkout')
            ->call('setSection', 'invalid')
            ->assertSet('storeId', '')
            ->assertSet('storeView', '')
            ->assertSet('status', '')
            ->assertSet('type', '')
            ->assertSet('section', 'quotes')
            ->assertSee('Guest active quote');
    }
}
