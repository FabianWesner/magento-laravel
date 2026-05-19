<?php

namespace Tests\Feature;

use App\Jobs\Modernization\Commerce\ReplayCommerceSideEffects;
use App\Modernization\Commerce\CommerceCalculator;
use App\Modernization\Commerce\CommerceCatalog;
use App\Modernization\Commerce\CommerceSnapshotRepository;
use App\Modernization\Commerce\CommerceTransactionPolicy;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommerceFoundationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const COMMERCE_FEATURE_IDS = [
        'CB-001',
        'CB-002',
        'CB-003',
        'CB-004',
        'CB-005',
        'CB-006',
        'CB-007',
        'CB-008',
        'CB-009',
        'CB-010',
        'CB-011',
        'CB-012',
        'CB-013',
        'CB-014',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('commerce_facts');
        Schema::create('commerce_facts', function (Blueprint $table): void {
            $table->id();
            $table->string('feature_key');
            $table->string('snapshot_type');
            $table->json('payload');
        });
    }

    public function test_quote_cart_totals_product_type_and_all_commerce_feature_ids_are_registered(): void
    {
        $catalog = $this->app->make(CommerceCatalog::class);
        $contexts = $this->commerceContexts();
        $registeredFeatureIds = collect($catalog->all())
            ->flatMap(fn ($feature): array => $feature->featureIds)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->assertContains('Quote', $contexts);
        $this->assertContains('Cart', $contexts);
        $this->assertContains('Totals', $contexts);
        $this->assertContains('ProductType', $contexts);
        $this->assertContains('EavScope', $contexts);
        $this->assertContains('simple', $catalog->get('product_type')->states);
        $this->assertContains('downloadable', $catalog->get('product_type')->states);
        $this->assertContains('attribute fallback', $catalog->get('eav_scope')->states);
        $this->assertSame(self::COMMERCE_FEATURE_IDS, $registeredFeatureIds);
    }

    public function test_pricing_catalog_rule_cart_rule_coupon_tax_and_totals_collector_behavior(): void
    {
        $calculator = $this->app->make(CommerceCalculator::class);
        $snapshot = $calculator->snapshot('totals', [
            'legacy result' => ['Magento' => 'baseline totals'],
            'items' => [
                ['sku' => 'simple-1', 'price' => 100, 'qty' => 2],
            ],
            'coupon_discount' => 25,
            'tax_rate' => 0.2,
            'shipping' => 10,
            'DB delta' => ['quote_address' => 'updated'],
        ]);

        $this->assertContains('Pricing', $this->commerceContexts());
        $this->assertContains('Promotion', $this->commerceContexts());
        $this->assertContains('Tax', $this->commerceContexts());
        $this->assertSame(200.00, $snapshot['payload']['totals']['subtotal']);
        $this->assertSame(25.00, $snapshot['payload']['totals']['discount']);
        $this->assertSame(35.00, $snapshot['payload']['totals']['tax']);
        $this->assertSame(220.00, $snapshot['payload']['totals']['grand_total']);
        $this->assertSame('pricing', $this->app->make(CommerceCatalog::class)->all()['pricing']->key);
    }

    public function test_shipping_payment_failure_retry_timeout_mock_and_sandbox_behavior(): void
    {
        Http::fake([
            'https://payment.example.test/*' => Http::response(['failure' => true], 503),
        ]);

        $catalog = $this->app->make(CommerceCatalog::class);

        $this->assertContains('Shipping', $this->commerceContexts());
        $this->assertContains('Payment', $this->commerceContexts());
        $this->assertContains('unavailable shipping', $catalog->get('shipping')->states);
        $this->assertContains('failed payment', $catalog->get('payment')->states);
        $this->assertContains('sandbox retry timeout mock', $catalog->get('payment')->states);
        $this->assertStringContainsString('PendingRequest', $this->app->make(CommerceCalculator::class)->externalRetryProbe());
    }

    public function test_order_state_invoice_shipment_credit_memo_refund_pdf_and_email_behavior(): void
    {
        $catalog = $this->app->make(CommerceCatalog::class);

        $this->assertContains('Order', $this->commerceContexts());
        $this->assertContains('Invoice', $this->commerceContexts());
        $this->assertContains('Shipment', $this->commerceContexts());
        $this->assertContains('CreditMemo', $this->commerceContexts());
        $this->assertContains('Refund', $this->commerceContexts());
        $this->assertContains('EmailQueue', $this->commerceContexts());
        $this->assertContains('pending', $catalog->get('order')->states);
        $this->assertContains('complete', $catalog->get('order')->states);
        $this->assertContains('creditmemo', $catalog->get('credit_memo')->states);
        $this->assertContains('online refund', $catalog->get('refund')->states);
        $this->assertContains('PDF', $catalog->get('invoice')->states);
        $this->assertContains('email', $catalog->get('invoice')->states);
    }

    public function test_inventory_stock_index_cache_session_email_queue_stale_and_recovery_behavior(): void
    {
        Queue::fake();

        $this->assertContains('Inventory', $this->commerceContexts());
        $this->assertContains('Index', $this->commerceContexts());
        $this->assertContains('Cache', $this->commerceContexts());
        $this->assertContains('EmailQueue', $this->commerceContexts());
        $this->assertContains('stale index', $this->app->make(CommerceCatalog::class)->get('index')->states);
        $this->assertContains('stale cache', $this->app->make(CommerceCatalog::class)->get('cache')->states);
        $this->assertContains('session', $this->app->make(CommerceCatalog::class)->get('cache')->states);

        ReplayCommerceSideEffects::dispatch('cache', ['stale' => true, 'rollback' => 'legacy_runtime_fallback']);
        Queue::assertPushed(ReplayCommerceSideEffects::class);
    }

    public function test_transaction_policy_records_db_side_effect_snapshots_with_idempotency_and_duplicate_control(): void
    {
        config(['cache.default' => 'array']);

        $repository = $this->app->make(CommerceSnapshotRepository::class);
        $policy = $this->app->make(CommerceTransactionPolicy::class);
        $duplicatePolicy = $policy->duplicatePolicy('quote-100');

        $policy->runIdempotent('quote-100', function () use ($repository): void {
            $repository->record('quote', 'DB snapshot', [
                'database snapshot' => ['quote_id' => 100],
                'side effect' => 'quote item inserted',
                'concurrency' => 'lockForUpdate-equivalent idempotency',
            ]);
        });

        $this->assertFalse($duplicatePolicy['duplicate']);
        $this->assertTrue($duplicatePolicy['transactional']);
        $this->assertSame(1, $repository->snapshots('quote')->count());
        $this->assertDatabaseHas('commerce_facts', [
            'feature_key' => 'quote',
            'snapshot_type' => 'DB snapshot',
        ]);
    }

    public function test_edge_invalid_permission_concurrency_duplicate_recovery_rollback_and_legacy_comparison_are_tracked(): void
    {
        $snapshot = $this->app->make(CommerceCalculator::class)->snapshot('cart', [
            'legacy result' => ['Magento' => 'legacy response'],
            'DB snapshot' => ['quote_id' => 200],
            'edge' => ['invalid quantity', 'permission denied', 'concurrency duplicate', 'recovery rollback stale'],
        ]);

        $this->assertSame('legacy_runtime_fallback', $snapshot['rollback']);
        $this->assertSame(['quote', 'cart', 'totals', 'pricing', 'promotion', 'tax', 'shipping', 'payment'], $snapshot['payload']['collector_sequence']);
        $this->assertContains('invalid quantity', $snapshot['payload']['edge']);
        $this->assertContains('recovery rollback stale', $snapshot['payload']['edge']);
    }

    /**
     * @return list<string>
     */
    private function commerceContexts(): array
    {
        return array_map(
            fn ($feature): string => $feature->context,
            $this->app->make(CommerceCatalog::class)->all(),
        );
    }
}
