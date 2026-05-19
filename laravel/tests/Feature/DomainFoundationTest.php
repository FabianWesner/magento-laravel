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

        $productSnapshot = $this->app->make(DomainQueryService::class)->snapshot('product', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $productPayloads = array_column($productSnapshot['payload']['rows'], 'payload');

        $this->assertSame('Product', $productSnapshot['feature']['context']);
        $this->assertSame('default', $productSnapshot['store_view']);
        $this->assertSame(2, $productSnapshot['payload']['count']);
        $this->assertSame(['simple-shirt', 'configurable-hoodie'], array_column($productPayloads, 'sku'));
        $this->assertSame(['simple', 'configurable'], array_column($productPayloads, 'type'));
        $this->assertSame('/women/simple-shirt.html', $productPayloads[0]['url']);
        $this->assertSame('USD', $productPayloads[0]['currency']);
        $this->assertSame(24.95, $productPayloads[0]['final_price']);
        $this->assertTrue($productPayloads[0]['stock']['is_in_stock']);
        $this->assertSame(24, $productPayloads[0]['stock']['qty']);
        $this->assertSame(4.5, $productPayloads[0]['rating']['summary']);
        $this->assertSame(12, $productPayloads[0]['rating']['reviews_count']);
        $this->assertSame('monogram', $productPayloads[0]['custom_options'][0]['code']);
        $this->assertSame(['configurable-hoodie'], $productPayloads[0]['related_skus']);
        $this->assertSame(['configurable-hoodie'], $productPayloads[0]['upsell_skus']);
        $this->assertSame(['downloadable-size-guide'], $productPayloads[0]['cross_sell_skus']);
        $this->assertSame([7001], $productPayloads[0]['media_entity_ids']);
        $this->assertSame([8001], $productPayloads[0]['downloadable_entity_ids']);
        $this->assertSame('size', $productPayloads[1]['configurable_options'][0]['attribute_code']);
        $this->assertSame('configurable-hoodie-black-m', $productPayloads[1]['variants'][0]['sku']);

        $deProductSnapshot = $this->app->make(DomainQueryService::class)->snapshot('product', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $deProductPayloads = array_column($deProductSnapshot['payload']['rows'], 'payload');

        $this->assertSame(2, $deProductSnapshot['payload']['count']);
        $this->assertSame(['simple-shirt', 'configurable-hoodie'], array_column($deProductPayloads, 'sku'));
        $this->assertSame('EUR', $deProductPayloads[0]['currency']);
        $this->assertSame('Konfigurierbarer Hoodie', $deProductPayloads[1]['name']);
        $this->assertSame('Groesse', $deProductPayloads[1]['configurable_options'][0]['label']);

        $mediaSnapshot = $this->app->make(DomainQueryService::class)->snapshot('product_media', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $mediaPayloads = array_column($mediaSnapshot['payload']['rows'], 'payload');

        $this->assertSame('ProductMedia', $mediaSnapshot['feature']['context']);
        $this->assertSame(2, $mediaSnapshot['payload']['count']);
        $this->assertSame('simple-shirt', $mediaPayloads[0]['sku']);
        $this->assertFalse($mediaPayloads[0]['missing_media']);
        $this->assertSame('catalog/product/simple-shirt/main.jpg', $mediaPayloads[0]['base_image']);
        $this->assertSame('Simple Shirt front', $mediaPayloads[0]['gallery'][0]['label']);
        $this->assertSame(['image', 'small_image', 'thumbnail'], $mediaPayloads[0]['gallery'][0]['types']);
        $this->assertSame('configurable-hoodie', $mediaPayloads[1]['sku']);
        $this->assertTrue($mediaPayloads[1]['missing_media']);
        $this->assertSame(['catalog/product/configurable-hoodie/missing-swatch.jpg'], $mediaPayloads[1]['missing_files']);

        $deMediaSnapshot = $this->app->make(DomainQueryService::class)->snapshot('product_media', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $this->assertSame(2, $deMediaSnapshot['payload']['count']);
        $this->assertSame('Einfaches Hemd Vorderseite', $deMediaSnapshot['payload']['rows'][0]['payload']['gallery'][0]['label']);

        $downloadableSnapshot = $this->app->make(DomainQueryService::class)->snapshot('downloadable', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $downloadablePayload = $downloadableSnapshot['payload']['rows'][0]['payload'];

        $this->assertSame('Downloadable', $downloadableSnapshot['feature']['context']);
        $this->assertSame(1, $downloadableSnapshot['payload']['count']);
        $this->assertSame('downloadable-size-guide', $downloadablePayload['sku']);
        $this->assertSame('downloadable/files/size-guide.pdf', $downloadablePayload['file']);
        $this->assertSame('customer_account_purchase', $downloadablePayload['permission']);
        $this->assertTrue($downloadablePayload['requires_login']);
        $this->assertFalse($downloadablePayload['is_shareable']);
        $this->assertSame(['General'], $downloadablePayload['customer_group_permissions']);
        $this->assertSame(['simple-shirt', 'configurable-hoodie'], $downloadablePayload['related_product_skus']);

        $deDownloadableSnapshot = $this->app->make(DomainQueryService::class)->snapshot('downloadable', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $this->assertSame(1, $deDownloadableSnapshot['payload']['count']);
        $this->assertSame('Groessentabelle PDF', $deDownloadableSnapshot['payload']['rows'][0]['payload']['title']);
        $this->assertSame(['Retail Kunde'], $deDownloadableSnapshot['payload']['rows'][0]['payload']['customer_group_permissions']);

        $defaultSearchSnapshot = $this->app->make(DomainQueryService::class)->snapshot('search', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $defaultSearchPayloads = array_column($defaultSearchSnapshot['payload']['rows'], 'payload');

        $this->assertSame('Search', $defaultSearchSnapshot['feature']['context']);
        $this->assertSame('default', $defaultSearchSnapshot['store_view']);
        $this->assertSame(3, $defaultSearchSnapshot['payload']['count']);
        $this->assertSame(['quick', 'advanced', 'quick'], array_column($defaultSearchPayloads, 'query_type'));
        $this->assertSame(['shirt', 'hoodie under 60', 'legacy jacket'], array_column($defaultSearchPayloads, 'query'));
        $this->assertSame([1, 1, 0], array_column($defaultSearchPayloads, 'results_count'));
        $this->assertSame(['simple-shirt'], $defaultSearchPayloads[0]['result_skus']);
        $this->assertSame(['configurable-hoodie'], $defaultSearchPayloads[1]['result_skus']);
        $this->assertSame([], $defaultSearchPayloads[2]['result_skus']);
        $this->assertSame('Gear', $defaultSearchPayloads[1]['filters']['category']);
        $this->assertSame(['top', 'tee'], $defaultSearchPayloads[0]['synonyms']);
        $this->assertNull($defaultSearchPayloads[0]['redirect']);
        $this->assertSame('/gear/configurable-hoodie.html', $defaultSearchPayloads[2]['redirect']['target']);
        $this->assertSame('/catalogsearch/result/?q=shirt', $defaultSearchPayloads[0]['canonical_url']);
        $this->assertSame('/rss/catalog/notifystock/?q=shirt', $defaultSearchPayloads[0]['rss_url']);
        $this->assertFalse($defaultSearchPayloads[0]['is_index_stale']);
        $this->assertTrue($defaultSearchPayloads[2]['is_index_stale']);

        foreach ($defaultSearchPayloads as $searchPayload) {
            $this->assertArrayHasKey('query_type', $searchPayload);
            $this->assertArrayHasKey('query', $searchPayload);
            $this->assertArrayHasKey('filters', $searchPayload);
            $this->assertArrayHasKey('results_count', $searchPayload);
            $this->assertArrayHasKey('result_skus', $searchPayload);
            $this->assertArrayHasKey('redirect', $searchPayload);
            $this->assertArrayHasKey('synonyms', $searchPayload);
            $this->assertArrayHasKey('canonical_url', $searchPayload);
            $this->assertArrayHasKey('rss_url', $searchPayload);
            $this->assertArrayHasKey('is_index_stale', $searchPayload);
        }

        $searchSnapshot = $this->app->make(DomainQueryService::class)->snapshot('search', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $deSearchPayloads = array_column($searchSnapshot['payload']['rows'], 'payload');

        $this->assertSame('Search', $searchSnapshot['feature']['context']);
        $this->assertSame('de', $searchSnapshot['store_view']);
        $this->assertSame(3, $searchSnapshot['payload']['count']);
        $this->assertSame(['hemd', 'hoodie bis 65', 'winterjacke'], array_column($deSearchPayloads, 'query'));
        $this->assertSame([1, 1, 0], array_column($deSearchPayloads, 'results_count'));
        $this->assertSame(['simple-shirt'], $deSearchPayloads[0]['result_skus']);
        $this->assertSame(['configurable-hoodie'], $deSearchPayloads[1]['result_skus']);
        $this->assertSame([], $deSearchPayloads[2]['result_skus']);
        $this->assertSame('Ausrustung', $deSearchPayloads[1]['filters']['category']);
        $this->assertSame(['shirt', 'oberteil'], $deSearchPayloads[0]['synonyms']);
        $this->assertNull($deSearchPayloads[0]['redirect']);
        $this->assertSame('/de/ausrustung/konfigurierbarer-hoodie.html', $deSearchPayloads[2]['redirect']['target']);
        $this->assertSame('/de/catalogsearch/result/?q=hemd', $deSearchPayloads[0]['canonical_url']);
        $this->assertSame('/de/rss/catalog/notifystock/?q=hemd', $deSearchPayloads[0]['rss_url']);
        $this->assertFalse($deSearchPayloads[0]['is_index_stale']);
        $this->assertTrue($deSearchPayloads[2]['is_index_stale']);

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
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'product_media',
            'entity_id' => 7004,
            'store_id' => 9002,
            'store_view' => 'de',
        ]);
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'downloadable',
            'entity_id' => 8002,
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
