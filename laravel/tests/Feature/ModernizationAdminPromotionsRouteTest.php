<?php

namespace Tests\Feature;

use App\Livewire\AdminPromotionsWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationAdminPromotionsRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_promotions_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.admin.promotions'));

        $response
            ->assertOk()
            ->assertSee('Modernization Admin Promotions')
            ->assertSee(route('modernization.assets.admin-promotions'))
            ->assertSee('Admin promotions workbench')
            ->assertSee('Admin promotions diagnostics')
            ->assertSee('AD-008')
            ->assertSee('CB-004')
            ->assertSee('CB-005')
            ->assertSee('Default Cart Percent Discount');
    }

    public function test_admin_promotions_asset_route_returns_css(): void
    {
        $response = $this->get(route('modernization.assets.admin-promotions'));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8');
    }

    public function test_admin_promotions_livewire_sections_filters_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(AdminPromotionsWorkbench::class)
            ->assertSee('4 cart rules')
            ->assertSee('6 catalog rules')
            ->assertSee('4 coupons')
            ->assertSee('2 reports')
            ->assertSee('6 problems')
            ->assertSee('Default Cart Percent Discount')
            ->assertSee('cart-percent-10-default')
            ->set('status', 'invalid_condition')
            ->assertSee('Invalid Cart Condition')
            ->assertDontSee('Default Cart Percent Discount')
            ->set('status', '')
            ->call('setSection', 'catalog_rules')
            ->assertSee('Scheduled Catalog Price Rule')
            ->assertSee('stale_catalog_rule_application')
            ->set('status', 'stale')
            ->assertSee('Stale Catalog Rule Application')
            ->assertDontSee('Scheduled Catalog Price Rule')
            ->set('status', '')
            ->call('setSection', 'coupons')
            ->assertSee('AUTO10-DEFAULT-0001')
            ->assertSee('WELCOME-USED-UP')
            ->set('status', 'exhausted')
            ->assertSee('WELCOME-USED-UP')
            ->assertDontSee('AUTO10-DEFAULT-0001')
            ->set('status', '')
            ->set('storeView', 'de')
            ->assertSee('AUTO12-DE-0001')
            ->assertDontSee('WELCOME-USED-UP')
            ->set('storeView', '')
            ->call('setSection', 'reports')
            ->assertSee('coupon')
            ->assertSee('aggregate_coupon_report_data')
            ->call('setSection', 'problems')
            ->assertSee('Invalid Cart Condition')
            ->assertSee('WELCOME-USED-UP')
            ->assertSee('stale_catalog_rule_application')
            ->set('query', 'not-present')
            ->assertSee('There are no problems facts matching this admin promotions scope.')
            ->call('clearFilters')
            ->assertSee('Default Cart Percent Discount')
            ->set('role', 'denied')
            ->assertSee('Permission denied for admin promotions role `denied`.')
            ->assertDontSee('Default Cart Percent Discount');
    }

    public function test_admin_promotions_livewire_normalizes_invalid_public_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(AdminPromotionsWorkbench::class)
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
            ->assertSet('section', 'cart_rules')
            ->assertSet('role', 'sales')
            ->assertSee('4 cart rules')
            ->assertDontSee('Store: bad-view');
    }
}
