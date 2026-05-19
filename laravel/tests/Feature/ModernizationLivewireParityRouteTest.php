<?php

namespace Tests\Feature;

use Tests\TestCase;

class ModernizationLivewireParityRouteTest extends TestCase
{
    public function test_livewire_parity_route_renders_storefront_and_admin_workbench(): void
    {
        $response = $this->get(route('modernization.livewire-parity'));

        $response
            ->assertOk()
            ->assertSee('Modernization Livewire Parity')
            ->assertSee(route('modernization.assets.livewire-parity'))
            ->assertSee('Livewire parity workbench')
            ->assertSee('Storefront parity')
            ->assertSee('SF-007 Cart')
            ->assertSee('Admin parity')
            ->assertSee('Filter: All')
            ->assertSee('AD-011 Cache, indexes, compiler');
    }
}
