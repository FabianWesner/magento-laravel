<?php

namespace Tests\Feature;

use App\Livewire\CustomerAccountWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationCustomerAccountRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_account_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.customer.account'));

        $response
            ->assertOk()
            ->assertSee('Modernization Customer Account')
            ->assertSee(route('modernization.assets.customer-account'))
            ->assertSee('Customer account workbench')
            ->assertSee('Customer account')
            ->assertSee('SF-010')
            ->assertSee('Maria Sommer')
            ->assertSee('Address Book');
    }

    public function test_customer_account_livewire_sections_scope_and_denied_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(CustomerAccountWorkbench::class)
            ->assertSee('Maria Sommer')
            ->assertSee('1 customer rows')
            ->call('setSection', 'addresses')
            ->assertSee('Default billing')
            ->assertSee('Portland')
            ->call('setSection', 'orders')
            ->assertSee('3 orders')
            ->assertSee('$184.70')
            ->set('storeView', 'de')
            ->assertSee('Lena Keller')
            ->assertDontSee('Maria Sommer')
            ->call('setSection', 'security')
            ->assertSee('explicit_non_sharing')
            ->set('storeView', 'missing')
            ->assertSee('There are no customer account facts for this scope.')
            ->call('clearScope')
            ->assertSee('Maria Sommer')
            ->set('role', 'denied')
            ->assertSee('Permission denied for customer role `denied`.');
    }
}
