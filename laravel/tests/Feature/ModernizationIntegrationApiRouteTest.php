<?php

namespace Tests\Feature;

use App\Livewire\IntegrationApiWorkbench;
use App\Modernization\Integrations\IntegrationDiagnosticsCatalog;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationIntegrationApiRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_integration_api_route_renders_livewire_workbench(): void
    {
        $response = $this->get(route('modernization.admin.integration-api'));

        $response
            ->assertOk()
            ->assertSee('Modernization Admin Integrations API')
            ->assertSee(route('modernization.assets.integration-api'))
            ->assertSee('Integrations API workbench')
            ->assertSee('AD-018, API-001 through API-006')
            ->assertSee('6 API contracts')
            ->assertSee('14 adapters')
            ->assertSee('14 sandbox adapters')
            ->assertSee('3 callbacks/OAuth')
            ->assertSee('9 attention rows')
            ->assertSee('SOAP API v1/v2')
            ->assertSee('/api/soap');

        $this->get(route('modernization.assets.integration-api'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8');
    }

    public function test_integration_diagnostics_catalog_combines_api_contracts_and_adapters(): void
    {
        $catalog = $this->app->make(IntegrationDiagnosticsCatalog::class);
        $rows = $catalog->rows();

        $this->assertCount(20, $rows);
        $this->assertSame([
            'api_contracts' => 6,
            'adapters' => 14,
            'sandbox_adapters' => 14,
            'callbacks' => 3,
            'attention' => 9,
        ], $catalog->summary($rows));

        $this->assertSame(['payment_gateway', 'webhook'], array_column($catalog->filtered(['status' => 'callback-required']), 'key'));
        $this->assertSame(['oauth'], array_column($catalog->filtered(['status' => 'oauth-required']), 'key'));
        $this->assertCount(4, $catalog->filtered(['feature_id' => 'API-004']));
        $this->assertSame(['payment_gateway'], array_column($catalog->filtered(['query' => 'PayPal']), 'key'));
    }

    public function test_integration_diagnostics_catalog_flags_missing_sandbox_metadata(): void
    {
        config()->set('integrations.adapters.manual_adapter', [
            'context' => 'ManualAdapter',
            'feature_ids' => ['API-006'],
            'endpoint' => 'https://manual.example.test/sync',
            'method' => 'POST',
            'config_path' => 'project/manual/api',
            'secret_key' => 'services.manual.token',
            'sandbox' => false,
        ]);

        $catalog = $this->app->make(IntegrationDiagnosticsCatalog::class);

        $rows = $catalog->filtered(['query' => 'ManualAdapter']);

        $this->assertCount(1, $rows);
        $this->assertSame('mock-required', $rows[0]['status']);
        $this->assertTrue($rows[0]['needs_attention']);
        $this->assertFalse($rows[0]['sandbox']);
        $this->assertSame('Adapter needs sandbox metadata before it can be verified.', $rows[0]['summary']);
    }

    public function test_admin_integration_api_livewire_filters_sections_empty_and_denied_states(): void
    {
        Livewire::test(IntegrationApiWorkbench::class)
            ->assertSee('6 API contracts')
            ->assertSee('SOAP API v1/v2')
            ->assertSee('REST/API2 OAuth')
            ->call('setSection', 'adapters')
            ->assertSee('14 adapters')
            ->assertSee('Payment Gateway')
            ->assertSee('Erp')
            ->call('setSection', 'callbacks')
            ->assertSee('Payment Gateway')
            ->assertSee('Webhook')
            ->assertSee('OAuth')
            ->call('setSection', 'problems')
            ->assertSee('9 attention rows')
            ->assertSee('SOAP API v1/v2')
            ->assertSee('Payment Gateway')
            ->assertSee('OAuth')
            ->set('featureId', 'AD-018')
            ->assertSee('1 attention rows')
            ->assertSee('OAuth')
            ->set('query', 'not-present')
            ->assertSee('There are no problems rows matching this integrations API scope.')
            ->call('clearFilters')
            ->call('setSection', 'adapters')
            ->set('query', 'PayPal')
            ->assertSee('1 adapters')
            ->assertSee('Payment Gateway')
            ->call('clearFilters')
            ->set('role', 'denied')
            ->assertSee('Permission denied for integration role `denied`.')
            ->assertDontSee('SOAP API v1/v2');
    }

    public function test_admin_integration_api_livewire_status_kind_and_read_only_role_filters(): void
    {
        Livewire::test(IntegrationApiWorkbench::class)
            ->set('role', 'read-only')
            ->assertSee('SOAP API v1/v2')
            ->assertDontSee('Permission denied')
            ->call('setSection', 'adapters')
            ->set('status', 'callback-required')
            ->assertSee('2 adapters')
            ->assertSee('Payment Gateway')
            ->assertSee('Webhook')
            ->assertDontSee('Shipping Carrier')
            ->set('status', 'attention')
            ->assertSee('3 adapters')
            ->assertSee('Payment Gateway')
            ->assertSee('Webhook')
            ->assertSee('OAuth')
            ->set('status', '')
            ->set('kind', 'api_contract')
            ->assertSee('0 adapters')
            ->assertSee('There are no adapters rows matching this integrations API scope.')
            ->call('setSection', 'contracts')
            ->assertSee('6 API contracts')
            ->assertSee('SOAP API v1/v2')
            ->assertDontSee('Payment Gateway');
    }

    public function test_admin_integration_api_livewire_normalizes_invalid_public_filter_state(): void
    {
        Livewire::test(IntegrationApiWorkbench::class)
            ->set('status', 'bad-status')
            ->set('kind', 'bad-kind')
            ->set('featureId', 'bad-feature')
            ->set('section', 'bad-section')
            ->assertSet('status', '')
            ->assertSet('kind', '')
            ->assertSet('featureId', '')
            ->assertSet('section', 'contracts')
            ->assertSee('6 API contracts');
    }
}
