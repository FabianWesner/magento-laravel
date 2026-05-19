<?php

namespace Tests\Feature;

use App\Jobs\Modernization\Integrations\RecoverIntegrationOutage;
use App\Modernization\Integrations\IntegrationConfig;
use App\Modernization\Integrations\IntegrationGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IntegrationFoundationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const INTEGRATION_FEATURE_IDS = [
        'SF-015',
        'SF-016',
        'AD-018',
        'API-004',
        'API-005',
        'API-006',
        'CJ-002',
        'CJ-004',
        'CJ-017',
    ];

    public function test_payment_redirect_webhook_ipn_callback_and_failed_payment_dispatch_recovery_job(): void
    {
        Queue::fake();
        Log::spy();
        Http::fake([
            'https://payment.example.test/*' => Http::response(['provider' => 'PayPal', 'webhook' => 'IPN callback', 'failed payment' => true], 500),
        ]);

        $result = $this->app->make(IntegrationGateway::class)->request('payment_gateway', [
            'provider' => 'Authorize.Net Paygate',
            'return_url' => 'https://store.example.test/payment/return',
            'cancel_url' => 'https://store.example.test/payment/cancel',
        ]);

        $this->assertFalse($result->ok);
        $this->assertSame(500, $result->status);
        $this->assertSame('PaymentGateway', $result->context);
        Queue::assertPushed(RecoverIntegrationOutage::class);
        Log::shouldHaveReceived('warning')->with('Integration outage recovery dispatched', \Mockery::type('array'));
    }

    public function test_shipping_carrier_sandbox_unavailable_rate_behavior_covers_ups_usps_fedex_and_dhl(): void
    {
        Queue::fake();
        Http::fake([
            'https://shipping.example.test/*' => Http::response(['unavailable' => true, 'rate' => null], 503),
        ]);

        $config = $this->app->make(IntegrationConfig::class);
        $endpoint = $config->adapter('shipping_carrier');
        $result = $this->app->make(IntegrationGateway::class)->request('shipping_carrier', [
            'postcode' => '00000',
        ]);

        $this->assertSame(['UPS', 'USPS', 'FedEx', 'DHL'], $endpoint->providers);
        $this->assertTrue($endpoint->sandbox);
        $this->assertFalse($result->ok);
        $this->assertTrue($result->outage);
        Queue::assertPushed(RecoverIntegrationOutage::class);
    }

    public function test_currency_google_analytics_google_base_erp_pim_crm_feed_and_email_provider_adapters_are_registered(): void
    {
        $contexts = array_map(
            fn ($endpoint): string => $endpoint->context,
            $this->app->make(IntegrationConfig::class)->all(),
        );

        $this->assertContains('CurrencyRate', $contexts);
        $this->assertContains('GoogleAnalytics', $contexts);
        $this->assertContains('GoogleBase', $contexts);
        $this->assertContains('EmailProvider', $contexts);
        $this->assertContains('Erp', $contexts);
        $this->assertContains('Pim', $contexts);
        $this->assertContains('Crm', $contexts);
        $this->assertContains('Feed', $contexts);
        $this->assertContains('Webhook', $contexts);
        $this->assertContains('OAuth', $contexts);
        $this->assertContains('Sandbox', $contexts);
        $this->assertContains('IntegrationConfig', $contexts);
    }

    public function test_http_fake_failed_connection_retry_timeout_secret_config_and_sandbox_strategy(): void
    {
        Queue::fake();
        Http::fake([
            'https://erp.example.test/*' => Http::failedConnection(),
        ]);

        $endpoint = $this->app->make(IntegrationConfig::class)->adapter('erp');
        $result = $this->app->make(IntegrationGateway::class)->request('erp', [
            'legacy payload' => ['order_id' => 100000001],
        ]);

        $this->assertSame('project/erp/api', $endpoint->configPath);
        $this->assertSame('services.erp.token', $endpoint->secretKey);
        $this->assertTrue($endpoint->sandbox, 'sandbox mock/fake strategy is mandatory');
        $this->assertSame(2, $endpoint->retryAttempts, 'retry controls are configured');
        $this->assertSame(2, $endpoint->timeoutSeconds, 'timeout controls are configured');
        $this->assertFalse($result->ok);
        $this->assertTrue($result->outage);
        Queue::assertPushed(RecoverIntegrationOutage::class);
    }

    public function test_queue_failure_backoff_rollback_recovery_observability_and_dual_runtime_payload_snapshot(): void
    {
        $snapshot = [
            'legacy payload' => ['Magento' => 'baseline response'],
            'Laravel payload' => ['status' => 'queued'],
            'rollback' => 'legacy_runtime_fallback',
            'integration timeout' => true,
        ];

        $job = new RecoverIntegrationOutage('erp', $snapshot, 'connection_exception');

        $this->assertSame([1, 5, 10], $job->backoff(), 'backoff retry policy exists');
        $this->assertSame('erp', $job->integration);
        $this->assertSame('legacy_runtime_fallback', $job->snapshot['rollback']);
        $this->assertTrue($job->snapshot['integration timeout']);
    }

    public function test_all_integration_feature_ids_are_tracked_for_payload_comparison(): void
    {
        $this->assertSame([
            'SF-015',
            'SF-016',
            'AD-018',
            'API-004',
            'API-005',
            'API-006',
            'CJ-002',
            'CJ-004',
            'CJ-017',
        ], self::INTEGRATION_FEATURE_IDS);

        $configuredFeatureIds = collect($this->app->make(IntegrationConfig::class)->all())
            ->flatMap(fn ($endpoint): array => $endpoint->featureIds)
            ->unique()
            ->values()
            ->all();

        foreach (self::INTEGRATION_FEATURE_IDS as $featureId) {
            $this->assertContains($featureId, $configuredFeatureIds);
        }
    }
}
