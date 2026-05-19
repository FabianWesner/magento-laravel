<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modernization\Domain\CommunicationService;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Modernization\Domain\ImportExportDataflow;
use App\Modernization\Domain\MediaStorage;
use App\Modernization\Domain\SeoUrlRewrite;
use App\Policies\Modernization\Domain\DomainPolicy;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DomainFoundationTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @var list<string>
     */
    private const DOMAIN_FEATURE_IDS = [
        'SF-001',
        'SF-002',
        'SF-003',
        'SF-004',
        'SF-005',
        'SF-006',
        'SF-010',
        'SF-011',
        'SF-013',
        'SF-014',
        'SF-016',
        'AD-002',
        'AD-003',
        'AD-004',
        'AD-007',
        'AD-009',
        'AD-013',
        'AD-015',
        'AD-017',
        'CB-012',
        'CJ-019',
        'CJ-022',
        'CJ-025',
    ];

    public function test_catalog_category_product_media_and_search_behavior_has_domain_snapshots(): void
    {
        $catalog = $this->app->make(DomainCatalog::class);
        $contexts = $this->domainContexts();

        $this->assertContains('Catalog', $contexts);
        $this->assertContains('Category', $contexts);
        $this->assertContains('Product', $contexts);
        $this->assertContains('ProductMedia', $contexts);
        $this->assertContains('Search', $contexts);
        $this->assertContains('Downloadable', $contexts);
        $this->assertContains('simple', $catalog->get('product')->states);
        $this->assertContains('configurable', $catalog->get('product')->states);
        $this->assertContains('bundle', $catalog->get('product')->states);
        $this->assertContains('filter', $catalog->get('catalog')->states);
        $this->assertContains('sort', $catalog->get('catalog')->states);
        $this->assertContains('swatch', $catalog->get('catalog')->states);
    }

    public function test_customer_address_wishlist_compare_review_tag_newsletter_and_contact_email_behavior(): void
    {
        Mail::fake();
        Notification::fake();

        $contexts = $this->domainContexts();
        $plan = $this->app->make(CommunicationService::class)->plan('send to friend', 'friend@example.test');

        $this->assertContains('Customer', $contexts);
        $this->assertContains('CustomerAddress', $contexts);
        $this->assertContains('Wishlist', $contexts);
        $this->assertContains('Compare', $contexts);
        $this->assertContains('Review', $contexts);
        $this->assertContains('Tag', $contexts);
        $this->assertContains('Newsletter', $contexts);
        $this->assertContains('Contact', $contexts);
        $this->assertTrue($plan['send to friend']);
        $this->assertTrue($this->app->make(CommunicationService::class)->plan('product alert', 'customer@example.test')['product alert']);
        $this->assertSame(Mail::class, $plan['mail_artifact']);
        $this->assertSame(Notification::class, $plan['notification_artifact']);
    }

    public function test_cms_page_block_widget_no_route_redirect_sitemap_rss_url_rewrite_and_seo_behavior(): void
    {
        $catalog = $this->app->make(DomainCatalog::class);
        $rewrite = $this->app->make(SeoUrlRewrite::class)->resolve('about-us', 'cms/page/view/page_id/4', 'de');

        $this->assertContains('CmsPage', $this->domainContexts());
        $this->assertContains('CmsBlock', $this->domainContexts());
        $this->assertContains('Widget', $this->domainContexts());
        $this->assertContains('Sitemap', $this->domainContexts());
        $this->assertContains('UrlRewrite', $this->domainContexts());
        $this->assertContains('no-route', $catalog->get('cms_page')->states);
        $this->assertContains('404', $catalog->get('cms_page')->states);
        $this->assertTrue($rewrite['redirect']);
        $this->assertTrue($rewrite['sitemap']);
        $this->assertTrue($rewrite['RSS']);
        $this->assertTrue($rewrite['SEO']);
        $this->assertSame('/de/about-us', $rewrite['canonical']);
    }

    public function test_import_export_dataflow_validation_and_failure_behavior(): void
    {
        $dataflow = $this->app->make(ImportExportDataflow::class);

        $this->assertContains('ImportExport', $this->domainContexts());
        $this->assertContains('Dataflow', $this->domainContexts());
        $this->assertSame([
            'rows' => 1,
            'format' => 'CSV',
            'batch' => true,
            'validation' => 'passed',
        ], $dataflow->validateCsvRows([
            ['sku' => 'simple-1', 'store_view' => 'default'],
        ]));

        $this->expectException(ValidationException::class);
        $dataflow->validateCsvRows([
            ['sku' => '', 'store_view' => ''],
        ]);
    }

    public function test_media_filesystem_traversal_missing_media_and_downloadable_behavior(): void
    {
        Storage::fake('domain-media');

        $media = $this->app->make(MediaStorage::class);
        $path = $media->putMedia('catalog/product/example.jpg', 'image-bytes', 'domain-media');

        Storage::disk('domain-media')->assertExists($path);
        Storage::disk('domain-media')->assertMissing('missing.jpg');
        $this->assertFalse($media->inspect('missing.jpg', 'domain-media')['exists'], 'missing media is reported');
        $this->assertTrue($media->inspect('downloadable/files/manual.pdf', 'domain-media')['downloadable']);

        $this->expectException(\InvalidArgumentException::class);
        $media->inspect('../etc/passwd', 'domain-media');
    }

    public function test_db_snapshot_store_scope_store_view_and_legacy_comparison_side_effects_are_tracked(): void
    {
        $this->seedDomainFact('catalog', 10, 1, 'default', ['Magento' => 'baseline', 'name' => 'Default category']);
        $this->seedDomainFact('catalog', 10, 2, 'de', ['Laravel' => 'snapshot', 'name' => 'DE category']);

        $snapshot = $this->app->make(DomainQueryService::class)->snapshot('catalog', [
            'store_id' => 2,
            'store_view' => 'de',
        ]);

        $this->assertSame('de', $snapshot['store_view']);
        $this->assertSame(1, $snapshot['payload']['count']);
        $this->assertSame('DE category', $snapshot['payload']['rows'][0]['payload']['name']);
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'catalog',
            'entity_id' => 10,
            'store_view' => 'de',
        ]);
    }

    public function test_domain_facts_migration_and_seed_data_support_domain_query_service(): void
    {
        $this->assertTrue(Schema::hasTable('domain_facts'));
        $this->assertTrue(Schema::hasColumns('domain_facts', [
            'id',
            'feature_key',
            'entity_id',
            'store_id',
            'store_view',
            'payload',
        ]));

        $this->seed(DomainFactSeeder::class);

        $catalogSnapshot = $this->app->make(DomainQueryService::class)->snapshot('catalog', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $defaultRows = $catalogSnapshot['payload']['rows'];
        $defaultPayloads = array_column($defaultRows, 'payload');

        $this->assertSame('Catalog', $catalogSnapshot['feature']['context']);
        $this->assertSame(2, $catalogSnapshot['payload']['count']);
        $this->assertSame(['simple-shirt', 'configurable-hoodie'], array_column($defaultPayloads, 'sku'));
        $this->assertSame(['Women', 'Gear'], array_column($defaultPayloads, 'category'));
        $this->assertSame(['simple', 'configurable'], array_column($defaultPayloads, 'type'));
        $this->assertSame('/women/simple-shirt.html', $defaultPayloads[0]['url']);
        $this->assertArrayHasKey('name', $defaultPayloads[0]);
        $this->assertArrayHasKey('price', $defaultPayloads[0]);
        $this->assertArrayHasKey('stock', $defaultPayloads[0]);
        $this->assertArrayHasKey('short_description', $defaultPayloads[0]);

        $deCatalogSnapshot = $this->app->make(DomainQueryService::class)->snapshot('catalog', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $this->assertSame(1, $deCatalogSnapshot['payload']['count']);
        $this->assertSame('Einfaches Hemd', $deCatalogSnapshot['payload']['rows'][0]['payload']['name']);

        $searchSnapshot = $this->app->make(DomainQueryService::class)->snapshot('search', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $this->assertSame('Search', $searchSnapshot['feature']['context']);
        $this->assertSame('hemd', $searchSnapshot['payload']['rows'][0]['payload']['query']);

        $customerSnapshot = $this->app->make(DomainQueryService::class)->snapshot('customer', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $customerPayload = $customerSnapshot['payload']['rows'][0]['payload'];

        $this->assertSame('Customer', $customerSnapshot['feature']['context']);
        $this->assertSame('default', $customerSnapshot['store_view']);
        $this->assertSame(1, $customerSnapshot['payload']['count']);
        $this->assertSame(5001, $customerSnapshot['payload']['rows'][0]['entity_id']);
        $this->assertSame('maria.sommer@example.test', $customerPayload['email']);
        $this->assertSame('Maria Sommer', $customerPayload['full_name']);
        $this->assertSame('General', $customerPayload['group']);
        $this->assertTrue($customerPayload['is_active']);
        $this->assertSame(3, $customerPayload['orders_count']);
        $this->assertSame(184.70, $customerPayload['lifetime_value']);
        $this->assertTrue($customerPayload['newsletter_subscribed']);
        $this->assertSame(6001, $customerPayload['default_billing_address_id']);
        $this->assertSame(6001, $customerPayload['default_shipping_address_id']);

        $deCustomerSnapshot = $this->app->make(DomainQueryService::class)->snapshot('customer', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $this->assertSame(1, $deCustomerSnapshot['payload']['count']);
        $this->assertSame('Lena Keller', $deCustomerSnapshot['payload']['rows'][0]['payload']['full_name']);
        $this->assertSame('Retail Kunde', $deCustomerSnapshot['payload']['rows'][0]['payload']['group']);

        $addressSnapshot = $this->app->make(DomainQueryService::class)->snapshot('customer_address', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $addressPayload = $addressSnapshot['payload']['rows'][0]['payload'];

        $this->assertSame('CustomerAddress', $addressSnapshot['feature']['context']);
        $this->assertSame('default', $addressSnapshot['store_view']);
        $this->assertSame(1, $addressSnapshot['payload']['count']);
        $this->assertSame(6001, $addressSnapshot['payload']['rows'][0]['entity_id']);
        $this->assertSame(5001, $addressPayload['customer_id']);
        $this->assertSame('billing_shipping', $addressPayload['address_type']);
        $this->assertSame(['101 Market Street', 'Suite 400'], $addressPayload['street']);
        $this->assertSame('Portland', $addressPayload['city']);
        $this->assertSame('Oregon', $addressPayload['region']);
        $this->assertSame('97204', $addressPayload['postcode']);
        $this->assertSame('US', $addressPayload['country_id']);
        $this->assertSame('+1-503-555-0198', $addressPayload['telephone']);
        $this->assertTrue($addressPayload['is_default_billing']);
        $this->assertTrue($addressPayload['is_default_shipping']);
        $this->assertContains('Portland, Oregon 97204', $addressPayload['formatted_lines']);

        $deAddressSnapshot = $this->app->make(DomainQueryService::class)->snapshot('customer_address', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $this->assertSame(1, $deAddressSnapshot['payload']['count']);
        $this->assertSame('DE', $deAddressSnapshot['payload']['rows'][0]['payload']['country_id']);
        $this->assertSame('10117 Berlin', $deAddressSnapshot['payload']['rows'][0]['payload']['formatted_lines'][3]);
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'category',
            'entity_id' => 2001,
            'store_id' => 9001,
            'store_view' => 'default',
        ]);
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'customer',
            'entity_id' => 5002,
            'store_id' => 9002,
            'store_view' => 'de',
        ]);
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'customer_address',
            'entity_id' => 6002,
            'store_id' => 9002,
            'store_view' => 'de',
        ]);
    }

    public function test_domain_permission_policy_and_all_feature_ids_are_tracked(): void
    {
        $policy = new DomainPolicy;

        $this->assertTrue($policy->viewDiagnostics($this->userWithRole('catalog')), 'authorized catalog admin can inspect domain diagnostics');
        $this->assertFalse($policy->viewDiagnostics($this->userWithRole('denied')));
        $this->assertSame(self::DOMAIN_FEATURE_IDS, [
            'SF-001',
            'SF-002',
            'SF-003',
            'SF-004',
            'SF-005',
            'SF-006',
            'SF-010',
            'SF-011',
            'SF-013',
            'SF-014',
            'SF-016',
            'AD-002',
            'AD-003',
            'AD-004',
            'AD-007',
            'AD-009',
            'AD-013',
            'AD-015',
            'AD-017',
            'CB-012',
            'CJ-019',
            'CJ-022',
            'CJ-025',
        ]);

        $configuredFeatureIds = collect($this->app->make(DomainCatalog::class)->all())
            ->flatMap(fn ($feature): array => $feature->featureIds)
            ->unique()
            ->values()
            ->all();

        foreach (self::DOMAIN_FEATURE_IDS as $featureId) {
            $this->assertContains($featureId, $configuredFeatureIds, "dual-runtime legacy comparison baseline covers {$featureId}");
        }
    }

    /**
     * @return list<string>
     */
    private function domainContexts(): array
    {
        return array_map(
            fn ($feature): string => $feature->context,
            $this->app->make(DomainCatalog::class)->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function seedDomainFact(string $featureKey, int $entityId, int $storeId, string $storeView, array $payload): void
    {
        DB::table('domain_facts')->insert([
            'feature_key' => $featureKey,
            'entity_id' => $entityId,
            'store_id' => $storeId,
            'store_view' => $storeView,
            'payload' => json_encode($payload),
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = new User;
        $user->forceFill([
            'id' => 401,
            'name' => "{$role} domain user",
            'email' => "{$role}-domain@example.test",
            'password' => Hash::make('secret'),
        ]);
        $user->setAttribute('role', $role);
        $user->exists = true;

        return $user;
    }
}
