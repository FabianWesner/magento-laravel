<?php

namespace Tests\Feature;

use App\Livewire\AdminCustomerWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationAdminCustomerRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_customer_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.admin.customer-management'));

        $response
            ->assertOk()
            ->assertSee('Modernization Admin Customer')
            ->assertSee(route('modernization.assets.admin-customer'))
            ->assertSee('Admin customer management workbench')
            ->assertSee('Admin customer diagnostics')
            ->assertSee('AD-007')
            ->assertSee('SF-010')
            ->assertSee('SF-011')
            ->assertSee('AD-009')
            ->assertSee('Maria Sommer')
            ->assertSee('maria.sommer@example.test');
    }

    public function test_admin_customer_asset_route_returns_css(): void
    {
        $response = $this->get(route('modernization.assets.admin-customer'));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8');
    }

    public function test_admin_customer_livewire_sections_filters_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(AdminCustomerWorkbench::class)
            ->assertSee('Maria Sommer')
            ->assertSee('1 customers')
            ->assertSee('1 addresses')
            ->assertSee('4 activity rows')
            ->assertSee('4 moderation rows')
            ->assertSee('4 problems')
            ->call('setSection', 'addresses')
            ->assertSee('Portland')
            ->assertSee('billing_shipping')
            ->set('section', 'activity')
            ->assertSee('Wishlist 9101')
            ->assertSee('Compare 9201')
            ->assertSee('guest_session_expired')
            ->set('type', 'wishlist')
            ->assertSee('Wishlist 9101')
            ->assertDontSee('Compare 9201')
            ->set('type', '')
            ->set('section', 'moderation')
            ->assertSee('Reliable shirt')
            ->assertSee('Awaiting moderation')
            ->set('status', 'pending')
            ->assertSee('Awaiting moderation')
            ->assertSee('needs-review')
            ->assertDontSee('Reliable shirt')
            ->set('status', '')
            ->set('section', 'problems')
            ->assertSee('empty_private')
            ->assertSee('guest_session_expired')
            ->assertSee('Awaiting moderation')
            ->set('storeView', 'de')
            ->set('section', 'customers')
            ->assertSee('Lena Keller')
            ->assertDontSee('Maria Sommer')
            ->set('query', 'not-present')
            ->assertSee('There are no customers facts matching this admin customer scope.')
            ->call('clearFilters')
            ->assertSee('Maria Sommer')
            ->set('role', 'denied')
            ->assertSee('Permission denied for admin customer role `denied`.');
    }

    public function test_admin_customer_livewire_normalizes_invalid_public_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(AdminCustomerWorkbench::class)
            ->set('storeId', 'invalid-store')
            ->set('storeView', 'invalid-view')
            ->set('status', 'invalid-status')
            ->set('type', 'invalid-type')
            ->set('section', 'invalid-section')
            ->set('role', 'invalid-role')
            ->assertSet('storeId', '')
            ->assertSet('storeView', 'default')
            ->assertSet('status', '')
            ->assertSet('type', '')
            ->assertSet('section', 'customers')
            ->assertSet('role', 'customer')
            ->assertSee('Maria Sommer');
    }
}
