<?php

namespace Tests\Feature;

use App\Modernization\Api\ExternalIntegrationProbe;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ApiContractFoundationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const API_FEATURE_IDS = [
        'API-001',
        'API-002',
        'API-003',
        'API-004',
        'API-005',
        'API-006',
    ];

    public function test_json_api_requests_expose_versioned_legacy_contracts(): void
    {
        $response = $this->getJson('/api/v1/contracts?protocol=REST/API2&role=admin role');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.feature_id', 'API-003')
            ->assertJsonPath('data.0.protocol', 'REST/API2')
            ->assertJsonPath('data.0.auth', 'OAuth token')
            ->assertJsonPath('meta.version', 'v1');
    }

    public function test_soap_and_xml_rpc_contracts_are_available_for_legacy_response_comparison(): void
    {
        $this->getJson('/api/v1/contracts/API-001')
            ->assertOk()
            ->assertJsonPath('data.protocol', 'SOAP')
            ->assertJsonPath('data.name', 'SOAP API v1/v2');

        $this->getJson('/api/v1/contracts/API-002')
            ->assertOk()
            ->assertJsonPath('data.protocol', 'XML-RPC')
            ->assertJsonPath('data.name', 'XML-RPC API');
    }

    public function test_rest_api2_oauth_roles_and_error_formats_have_status_codes(): void
    {
        $this->getJson('/api/v1/contracts/API-003')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json): AssertableJson => $json
                ->where('data.feature_id', 'API-003')
                ->where('data.auth', 'OAuth token')
                ->where('data.roles.0', 'admin role')
                ->where('data.roles.1', 'customer role')
                ->where('data.roles.2', 'guest role')
                ->etc());

        $this->getJson('/api/v1/contracts/API-999')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'api_contract_not_found')
            ->assertJsonPath('error.status', 404);

        $this->postJson('/api/v1/contracts')
            ->assertStatus(405)
            ->assertJsonPath('error.code', 'api_contracts_are_read_only')
            ->assertJsonPath('error.status', 405);
    }

    public function test_payment_shipping_external_integration_mocks_use_timeout_and_retry_probe(): void
    {
        Http::fake([
            'https://payment.example.test/health' => Http::response(['status' => 'ok'], 200),
            'https://shipping.example.test/health' => Http::response(['status' => 'ok'], 200),
        ]);

        $probe = $this->app->make(ExternalIntegrationProbe::class);

        $payment = $probe->probe('payment', 'https://payment.example.test/health');
        $shipping = $probe->probe('shipping', 'https://shipping.example.test/health');

        $this->assertSame(['kind' => 'payment', 'ok' => true, 'status' => 200, 'retry' => 2, 'timeout' => 2], $payment);
        $this->assertSame(['kind' => 'shipping', 'ok' => true, 'status' => 200, 'retry' => 2, 'timeout' => 2], $shipping);
    }

    public function test_all_api_feature_ids_are_in_the_contract_inventory(): void
    {
        $response = $this->getJson('/api/v1/contracts');

        $response->assertOk();
        $this->assertSame(self::API_FEATURE_IDS, array_column($response->json('data'), 'feature_id'));
    }

    public function test_openapi_documentation_tracks_versioned_contract_paths_security_and_responses(): void
    {
        $path = base_path('openapi.yaml');

        $this->assertFileExists($path);

        $openApi = file_get_contents($path);
        $this->assertIsString($openApi);
        $this->assertStringContainsString('openapi: 3.1.0', $openApi);
        $this->assertStringContainsString('version: 1.0.0', $openApi);
        $this->assertStringContainsString('/contracts:', $openApi);
        $this->assertStringContainsString('/contracts/{contract}:', $openApi);
        $this->assertStringContainsString('LegacyOAuth', $openApi);
        $this->assertStringContainsString('responses:', $openApi);

        foreach (self::API_FEATURE_IDS as $featureId) {
            $this->assertStringContainsString($featureId, $openApi);
        }
    }
}
