<?php

namespace Tests\Feature;

use App\Livewire\AdminSalesFulfillmentWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationAdminSalesFulfillmentRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_sales_fulfillment_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.admin.sales-fulfillment'));

        $response
            ->assertOk()
            ->assertSee('Modernization Admin Sales Fulfillment')
            ->assertSee(route('modernization.assets.admin-sales-fulfillment'))
            ->assertSee('Admin sales fulfillment workbench')
            ->assertSee('Admin sales fulfillment diagnostics')
            ->assertSee('AD-005')
            ->assertSee('AD-006')
            ->assertSee('100000001')
            ->assertSee('Payment Review Customer');
    }

    public function test_admin_sales_fulfillment_asset_route_returns_css(): void
    {
        $response = $this->get(route('modernization.assets.admin-sales-fulfillment'));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8');
    }

    public function test_admin_sales_fulfillment_livewire_sections_filters_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(AdminSalesFulfillmentWorkbench::class)
            ->assertSee('4 orders')
            ->assertSee('3 invoices')
            ->assertSee('3 shipments')
            ->assertSee('3 credit memos')
            ->assertSee('4 transactions')
            ->assertSee('4 problems')
            ->assertSee('100000001')
            ->assertSee('Payment Review Customer')
            ->set('status', 'payment_review')
            ->assertSee('100000002')
            ->assertDontSee('100000001')
            ->set('status', '')
            ->call('setSection', 'invoices')
            ->assertSee('100000001-1')
            ->assertSee('100000004-1')
            ->set('status', 'partial')
            ->assertSee('100000004-1')
            ->assertDontSee('100000001-1')
            ->set('status', '')
            ->call('setSection', 'shipments')
            ->assertSee('TRACK1001')
            ->assertSee('tracking_number_missing')
            ->set('status', 'tracking_pending')
            ->assertSee('100000004-1')
            ->assertDontSee('TRACK1001')
            ->set('status', '')
            ->call('setSection', 'credit_memos')
            ->assertSee('gateway_refund_timeout')
            ->set('status', 'failed')
            ->assertSee('100000002-1')
            ->assertDontSee('100000003-1')
            ->set('status', '')
            ->call('setSection', 'transactions')
            ->assertSee('auth-review-100000002')
            ->assertSee('refund-100000003')
            ->set('storeView', 'de')
            ->assertSee('auth-100000004')
            ->assertDontSee('auth-review-100000002')
            ->set('storeView', '')
            ->call('setSection', 'problems')
            ->assertSee('Payment Review Customer')
            ->assertSee('tracking_number_missing')
            ->assertSee('gateway_refund_timeout')
            ->assertSee('auth-review-100000002')
            ->set('query', 'not-present')
            ->assertSee('There are no problems facts matching this admin sales fulfillment scope.')
            ->call('clearFilters')
            ->assertSee('100000001')
            ->set('role', 'denied')
            ->assertSee('Permission denied for admin sales fulfillment role `denied`.')
            ->assertDontSee('Payment Review Customer');
    }

    public function test_admin_sales_fulfillment_livewire_normalizes_invalid_public_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(AdminSalesFulfillmentWorkbench::class)
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
            ->assertSet('section', 'orders')
            ->assertSet('role', 'sales')
            ->assertSee('4 orders')
            ->assertDontSee('Store: bad-view');
    }
}
