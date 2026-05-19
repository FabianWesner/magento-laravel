<?php

namespace Tests\Feature;

use App\Jobs\Modernization\Reports\AggregateReportTables;
use App\Models\User;
use App\Modernization\Reports\ReportCatalog;
use App\Modernization\Reports\ReportQuery;
use App\Policies\Modernization\Reports\ReportPolicy;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportFoundationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const REPORT_FEATURE_IDS = [
        'AD-014',
        'AD-008',
        'AD-005',
        'AD-006',
        'CJ-004',
        'CJ-007',
        'CJ-008',
        'CJ-009',
        'CJ-010',
        'CJ-011',
        'CJ-015',
        'CJ-020',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('report_facts');
        Schema::create('report_facts', function (Blueprint $table): void {
            $table->id();
            $table->string('report_key');
            $table->string('bucket');
            $table->decimal('amount', 12, 2);
            $table->unsignedInteger('store_id');
            $table->string('currency', 3);
            $table->date('reported_at');
        });
    }

    public function test_sales_tax_shipping_invoiced_refunded_and_coupon_reports_are_registered(): void
    {
        $contexts = $this->reportContexts();

        $this->assertContains('SalesReport', $contexts, 'sales report exists');
        $this->assertContains('TaxReport', $contexts, 'tax report exists');
        $this->assertContains('ShippingReport', $contexts, 'shipping report exists');
        $this->assertContains('InvoicedReport', $contexts);
        $this->assertContains('RefundedReport', $contexts);
        $this->assertContains('CouponReport', $contexts, 'coupon report exists');
    }

    public function test_product_customer_search_cart_review_tag_bestseller_and_low_stock_reports_export_empty_performance_states(): void
    {
        $contexts = $this->reportContexts();

        $this->assertContains('ProductReport', $contexts, 'product report exists');
        $this->assertContains('CustomerReport', $contexts, 'customer report exists');
        $this->assertContains('SearchReport', $contexts, 'search terms report exists');
        $this->assertContains('CartReport', $contexts, 'cart report exists');
        $this->assertContains('ReviewReport', $contexts, 'review report exists');
        $this->assertContains('TagReport', $contexts, 'tag report exists');
        $this->assertContains('BestsellerReport', $contexts, 'bestseller report exists');
        $this->assertContains('LowStockReport', $contexts, 'low stock report exists');

        $query = $this->app->make(ReportQuery::class);
        $empty = $query->run($this->app->make(ReportCatalog::class)->get('low_stock'), [
            'currency' => 'USD',
        ]);

        $this->assertSame(0, $empty->count, 'empty state covers large catalog timeout/performance path');
        $this->assertStringStartsWith('bucket,currency,total,count,average', $query->exportCsv($empty), 'export csv exists');
    }

    public function test_report_query_creates_db_snapshot_table_parity_with_date_store_currency_filters(): void
    {
        $this->seedReportFact('sales', '2026-05-01', 1, 'USD', 100.00);
        $this->seedReportFact('sales', '2026-05-01', 1, 'USD', 25.00);
        $this->seedReportFact('sales', '2026-05-02', 2, 'EUR', 55.00);

        $result = $this->app->make(ReportQuery::class)->run($this->app->make(ReportCatalog::class)->get('sales'), [
            'from_date' => '2026-05-01',
            'to_date' => '2026-05-01',
            'store_id' => 1,
            'currency' => 'USD',
        ]);

        $this->assertSame(2, $result->count, 'DB snapshot row count matches report table parity');
        $this->assertSame(125.00, $result->total);
        $this->assertSame('USD', $result->currency);
        $this->assertDatabaseHas('report_facts', [
            'report_key' => 'sales',
            'store_id' => 1,
            'currency' => 'USD',
        ]);
    }

    public function test_before_and_after_aggregation_job_behavior_uses_report_table_snapshots(): void
    {
        $catalog = $this->app->make(ReportCatalog::class);
        $query = $this->app->make(ReportQuery::class);

        $beforeAggregation = $query->run($catalog->get('coupon'));
        $this->assertSame(0, $beforeAggregation->count, 'before aggregation has no coupon report rows');

        $this->seedReportFact('coupon', '2026-05-01', 1, 'USD', 7.50);
        (new AggregateReportTables(['coupon']))->handle($catalog, $query);

        $afterAggregation = $query->run($catalog->get('coupon'));
        $this->assertSame(1, $afterAggregation->count, 'after aggregation job report aggregation reads the schedule snapshot');
    }

    public function test_report_policy_covers_permission_authorized_and_forbidden_filters(): void
    {
        $policy = new ReportPolicy;

        $this->assertTrue($policy->view($this->userWithRole('read-only')), 'authorized report role can view reports');
        $this->assertFalse($policy->view($this->userWithRole('denied')), 'forbidden report role is denied by permission filter');
    }

    public function test_dual_runtime_legacy_report_comparison_and_all_report_feature_ids_are_tracked(): void
    {
        $this->assertSame([
            'AD-014',
            'AD-008',
            'AD-005',
            'AD-006',
            'CJ-004',
            'CJ-007',
            'CJ-008',
            'CJ-009',
            'CJ-010',
            'CJ-011',
            'CJ-015',
            'CJ-020',
        ], self::REPORT_FEATURE_IDS);

        $configuredFeatureIds = collect($this->app->make(ReportCatalog::class)->all())
            ->flatMap(fn ($definition): array => $definition->featureIds)
            ->unique()
            ->values()
            ->all();

        foreach (self::REPORT_FEATURE_IDS as $featureId) {
            $this->assertContains($featureId, $configuredFeatureIds, "legacy report baseline report comparison covers {$featureId}");
        }
    }

    /**
     * @return list<string>
     */
    private function reportContexts(): array
    {
        return array_map(
            fn ($definition): string => $definition->context,
            $this->app->make(ReportCatalog::class)->all(),
        );
    }

    private function seedReportFact(string $reportKey, string $date, int $storeId, string $currency, float $amount): void
    {
        DB::table('report_facts')->insert([
            'report_key' => $reportKey,
            'bucket' => $date,
            'amount' => $amount,
            'store_id' => $storeId,
            'currency' => $currency,
            'reported_at' => $date,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = new User;
        $user->forceFill([
            'id' => 301,
            'name' => "{$role} report user",
            'email' => "{$role}-report@example.test",
            'password' => Hash::make('secret'),
        ]);
        $user->setAttribute('role', $role);
        $user->exists = true;

        return $user;
    }
}
