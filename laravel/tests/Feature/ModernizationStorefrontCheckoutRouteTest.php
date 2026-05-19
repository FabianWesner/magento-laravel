<?php

namespace Tests\Feature;

use App\Livewire\StorefrontCheckoutWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationStorefrontCheckoutRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_storefront_checkout_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.storefront.checkout'));

        $response
            ->assertOk()
            ->assertSee('Modernization Storefront Checkout')
            ->assertSee(route('modernization.assets.storefront-checkout'))
            ->assertSee('Checkout diagnostics workbench')
            ->assertSee('Storefront checkout diagnostics')
            ->assertSee('Checkout steps')
            ->assertSee('SF-008')
            ->assertSee('Guest billing step ready')
            ->assertSee('Shipping method required');
    }

    public function test_storefront_checkout_css_asset_route_is_available(): void
    {
        $response = $this->get(route('modernization.assets.storefront-checkout'));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8');
    }

    public function test_storefront_checkout_livewire_filters_sections_store_scope_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(StorefrontCheckoutWorkbench::class)
            ->assertSee('Guest billing step ready')
            ->assertSee('Shipping method required')
            ->assertSee('5 steps')
            ->assertSee('4 payments')
            ->assertSee('3 review rows')
            ->assertSee('3 multishipping rows')
            ->assertSee('7 problems')
            ->set('status', 'method_required')
            ->assertSee('Shipping method required')
            ->assertDontSee('Guest billing step ready')
            ->call('setSection', 'payments')
            ->set('status', 'failed_payment')
            ->assertSee('Failed card authorization')
            ->assertDontSee('Check money order ready')
            ->call('setSection', 'review')
            ->set('status', 'agreement_required')
            ->assertSee('Review blocked by agreement')
            ->call('setSection', 'multishipping')
            ->set('status', '')
            ->set('storeView', 'de')
            ->assertSee('DE multishipping methods ready')
            ->assertDontSee('Multishipping addresses ready')
            ->call('setSection', 'problems')
            ->assertSee('DE invalid shipping address')
            ->set('query', 'not-present')
            ->assertSee('There are no problems facts matching this checkout scope.')
            ->call('clearFilters')
            ->assertSee('Guest billing step ready')
            ->set('role', 'denied')
            ->assertSee('Permission denied for storefront checkout role `denied`.');
    }

    public function test_storefront_checkout_livewire_normalizes_invalid_public_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(StorefrontCheckoutWorkbench::class)
            ->set('storeId', '9999')
            ->set('storeView', 'fr')
            ->set('status', 'save_order')
            ->set('type', 'order')
            ->call('setSection', 'invalid')
            ->assertSet('storeId', '')
            ->assertSet('storeView', '')
            ->assertSet('status', '')
            ->assertSet('type', '')
            ->assertSet('section', 'steps')
            ->assertSee('Guest billing step ready');
    }
}
