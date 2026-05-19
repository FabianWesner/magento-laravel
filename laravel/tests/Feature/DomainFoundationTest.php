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
        'AD-011',
        'AD-013',
        'AD-015',
        'AD-017',
        'CB-012',
        'CB-013',
        'CJ-016',
        'CJ-019',
        'CJ-021',
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
        $newsletterPlan = $this->app->make(CommunicationService::class)->plan('newsletter', 'subscriber@example.test');

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
        $this->assertTrue($newsletterPlan['newsletter']);
        $this->assertSame('domain-communications', $plan['queue']);
        $this->assertSame(Mail::class, $plan['mail_artifact']);
        $this->assertSame(Notification::class, $plan['notification_artifact']);
    }

    public function test_domain_fact_seeder_provides_newsletter_contact_and_email_queue_snapshots(): void
    {
        $this->seed(DomainFactSeeder::class);

        $newsletterSnapshot = $this->app->make(DomainQueryService::class)->snapshot('newsletter', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $newsletterPayloads = array_column($newsletterSnapshot['payload']['rows'], 'payload');

        $this->assertSame('Newsletter', $newsletterSnapshot['feature']['context']);
        $this->assertSame('default', $newsletterSnapshot['store_view']);
        $this->assertSame(3, $newsletterSnapshot['payload']['count']);
        $this->assertSame(['subscribed', 'unsubscribed', 'problem_report'], array_column($newsletterPayloads, 'state'));
        $this->assertSame(['sent', 'queued', 'failed'], array_column(array_column($newsletterPayloads, 'email'), 'delivery_status'));
        $this->assertSame('domain-communications', $newsletterPayloads[0]['email']['queue']);
        $this->assertSame('en_US', $newsletterPayloads[0]['store_scope']['locale']);
        $this->assertSame('hard_bounce', $newsletterPayloads[2]['problem_report']['type']);
        $this->assertFalse($newsletterPayloads[2]['permissions']['can_view_recipient']);
        $this->assertTrue($newsletterPayloads[2]['permissions']['safe_payload']);
        $this->assertSame('suppression_list_protects_recipient', $newsletterPayloads[2]['permissions']['denied_reason']);

        foreach ($newsletterPayloads as $newsletterPayload) {
            $this->assertArrayHasKey('newsletter_subscription_id', $newsletterPayload);
            $this->assertArrayHasKey('subscriber_status', $newsletterPayload);
            $this->assertArrayHasKey('store_scope', $newsletterPayload);
            $this->assertArrayHasKey('email', $newsletterPayload);
            $this->assertArrayHasKey('permissions', $newsletterPayload);
            $this->assertArrayHasKey('ui', $newsletterPayload);
            $this->assertSame(9001, $newsletterPayload['store_scope']['store_id']);
            $this->assertSame('default', $newsletterPayload['store_scope']['store_view']);
            $this->assertSame('domain-communications', $newsletterPayload['email']['queue']);
            $this->assertTrue($newsletterPayload['permissions']['safe_payload']);
        }

        $deNewsletterSnapshot = $this->app->make(DomainQueryService::class)->snapshot('newsletter', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $deNewsletterPayloads = array_column($deNewsletterSnapshot['payload']['rows'], 'payload');

        $this->assertSame('de', $deNewsletterSnapshot['store_view']);
        $this->assertSame(3, $deNewsletterSnapshot['payload']['count']);
        $this->assertSame(['subscribed', 'unsubscribed', 'problem_report'], array_column($deNewsletterPayloads, 'state'));
        $this->assertSame(['sent', 'queued', 'failed'], array_column(array_column($deNewsletterPayloads, 'email'), 'delivery_status'));
        $this->assertSame('de_DE', $deNewsletterPayloads[0]['store_scope']['locale']);
        $this->assertSame('Problembericht', $deNewsletterPayloads[2]['ui']['badge']);
        $this->assertSame('recipient_complaint', $deNewsletterPayloads[2]['email']['failure_reason']);

        $contactSnapshot = $this->app->make(DomainQueryService::class)->snapshot('contact', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $contactPayloads = array_column($contactSnapshot['payload']['rows'], 'payload');

        $this->assertSame('Contact', $contactSnapshot['feature']['context']);
        $this->assertSame('default', $contactSnapshot['store_view']);
        $this->assertSame(6, $contactSnapshot['payload']['count']);
        $this->assertSame([
            'contact_form',
            'contact_form',
            'send_to_friend',
            'product_alert',
            'product_alert',
            'send_to_friend',
        ], array_column($contactPayloads, 'communication_type'));
        $this->assertSame(['valid', 'invalid', 'queued', 'sent', 'failed', 'permission_denied'], array_column($contactPayloads, 'state'));
        $this->assertSame(['queued', 'not_queued', 'queued', 'sent', 'failed', 'not_queued'], array_column(array_column($contactPayloads, 'email'), 'delivery_status'));
        $this->assertTrue($contactPayloads[0]['form']['is_valid']);
        $this->assertSame([], $contactPayloads[0]['form']['validation_errors']);
        $this->assertFalse($contactPayloads[1]['form']['is_valid']);
        $this->assertSame('Email must be valid.', $contactPayloads[1]['form']['validation_errors']['email']);
        $this->assertSame('simple-shirt', $contactPayloads[2]['product_sku']);
        $this->assertSame('alex.friend@example.test', $contactPayloads[2]['recipients'][0]['email']);
        $this->assertSame('stock', $contactPayloads[3]['alert_type']);
        $this->assertSame(0, $contactPayloads[3]['alert']['stock_was']);
        $this->assertSame(8, $contactPayloads[3]['alert']['stock_is']);
        $this->assertSame('price', $contactPayloads[4]['alert_type']);
        $this->assertSame(29.95, $contactPayloads[4]['alert']['price_was']);
        $this->assertSame(24.95, $contactPayloads[4]['alert']['price_is']);
        $this->assertSame('mail_transport_failed', $contactPayloads[4]['email']['failure_reason']);
        $this->assertFalse($contactPayloads[5]['permissions']['can_view_message']);
        $this->assertTrue($contactPayloads[5]['permissions']['safe_payload']);
        $this->assertSame('customer_session_required', $contactPayloads[5]['permissions']['denied_reason']);
        $this->assertNull($contactPayloads[5]['email']['recipient']);
        $this->assertSame(0, $contactPayloads[5]['recipient_count']);

        foreach ($contactPayloads as $contactPayload) {
            $this->assertArrayHasKey('communication_id', $contactPayload);
            $this->assertArrayHasKey('communication_type', $contactPayload);
            $this->assertArrayHasKey('state', $contactPayload);
            $this->assertArrayHasKey('store_scope', $contactPayload);
            $this->assertArrayHasKey('email', $contactPayload);
            $this->assertArrayHasKey('permissions', $contactPayload);
            $this->assertArrayHasKey('ui', $contactPayload);
            $this->assertSame(9001, $contactPayload['store_scope']['store_id']);
            $this->assertSame('default', $contactPayload['store_scope']['store_view']);
            $this->assertSame('domain-communications', $contactPayload['email']['queue']);
            $this->assertTrue($contactPayload['permissions']['safe_payload']);
        }

        $deContactSnapshot = $this->app->make(DomainQueryService::class)->snapshot('contact', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $deContactPayloads = array_column($deContactSnapshot['payload']['rows'], 'payload');

        $this->assertSame('de', $deContactSnapshot['store_view']);
        $this->assertSame(4, $deContactSnapshot['payload']['count']);
        $this->assertSame(['valid', 'invalid', 'queued', 'failed'], array_column($deContactPayloads, 'state'));
        $this->assertSame(['sent', 'not_queued', 'queued', 'failed'], array_column(array_column($deContactPayloads, 'email'), 'delivery_status'));
        $this->assertSame('de_DE', $deContactPayloads[0]['store_scope']['locale']);
        $this->assertSame('Rueckfrage zur Bestellung', $deContactPayloads[0]['form']['subject']);
        $this->assertSame('E-Mail muss gueltig sein.', $deContactPayloads[1]['form']['validation_errors']['email']);
        $this->assertSame('mia.freundin@example.test', $deContactPayloads[2]['recipients'][0]['email']);
        $this->assertSame('price', $deContactPayloads[3]['alert_type']);
        $this->assertSame(31.95, $deContactPayloads[3]['alert']['price_was']);
        $this->assertSame(26.95, $deContactPayloads[3]['alert']['price_is']);
        $this->assertSame('mail_transport_failed', $deContactPayloads[3]['email']['failure_reason']);

        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'newsletter',
            'entity_id' => 10606,
            'store_id' => 9002,
            'store_view' => 'de',
        ]);
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'contact',
            'entity_id' => 10710,
            'store_id' => 9002,
            'store_view' => 'de',
        ]);
    }

    public function test_domain_fact_seeder_provides_cache_index_cron_and_lock_snapshots(): void
    {
        $this->seed(DomainFactSeeder::class);

        $catalog = $this->app->make(DomainCatalog::class);

        $this->assertContains('Cache', $this->domainContexts());
        $this->assertContains('Index', $this->domainContexts());
        $this->assertContains('invalidated', $catalog->get('cache')->states);
        $this->assertContains('stale cache', $catalog->get('cache')->states);
        $this->assertContains('processing', $catalog->get('index')->states);
        $this->assertContains('update required', $catalog->get('index')->states);

        $cacheSnapshot = $this->app->make(DomainQueryService::class)->snapshot('cache', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $cachePayloads = array_column($cacheSnapshot['payload']['rows'], 'payload');

        $this->assertSame('Cache', $cacheSnapshot['feature']['context']);
        $this->assertSame('default', $cacheSnapshot['store_view']);
        $this->assertSame(4, $cacheSnapshot['payload']['count']);
        $this->assertSame(['config', 'block_html', 'layout', 'compiler'], array_column($cachePayloads, 'cache_type'));
        $this->assertSame(['clean', 'invalidated', 'clean', 'disabled'], array_column($cachePayloads, 'status'));
        $this->assertFalse($cachePayloads[0]['is_stale']);
        $this->assertTrue($cachePayloads[1]['is_stale']);
        $this->assertSame('cms_block_save_invalidated_block_html', $cachePayloads[1]['stale_reason']);
        $this->assertContains('BLOCK_HTML', $cachePayloads[1]['cache_tags']);
        $this->assertSame('Retire unless project overlay requires compiler parity', $cachePayloads[3]['compiler']['retained_decision']);
        $this->assertSame('core_clean_cache', $cachePayloads[0]['cron']['job']);
        $this->assertSame('30 2 * * *', $cachePayloads[0]['cron']['schedule']);

        foreach ($cachePayloads as $cachePayload) {
            $this->assertArrayHasKey('operation_type', $cachePayload);
            $this->assertArrayHasKey('cache_type', $cachePayload);
            $this->assertArrayHasKey('status', $cachePayload);
            $this->assertArrayHasKey('legacy_status', $cachePayload);
            $this->assertArrayHasKey('is_stale', $cachePayload);
            $this->assertArrayHasKey('actions', $cachePayload);
            $this->assertArrayHasKey('ui', $cachePayload);
        }

        $deCacheSnapshot = $this->app->make(DomainQueryService::class)->snapshot('cache', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $deCachePayloads = array_column($deCacheSnapshot['payload']['rows'], 'payload');

        $this->assertSame('de', $deCacheSnapshot['store_view']);
        $this->assertSame(2, $deCacheSnapshot['payload']['count']);
        $this->assertSame(['config', 'translate'], array_column($deCachePayloads, 'cache_type'));
        $this->assertSame(['clean', 'invalidated'], array_column($deCachePayloads, 'status'));
        $this->assertTrue($deCachePayloads[1]['is_stale']);
        $this->assertSame('store_view_locale_translation_change', $deCachePayloads[1]['stale_reason']);

        $indexSnapshot = $this->app->make(DomainQueryService::class)->snapshot('index', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $indexPayloads = array_column($indexSnapshot['payload']['rows'], 'payload');

        $this->assertSame('Index', $indexSnapshot['feature']['context']);
        $this->assertSame('default', $indexSnapshot['store_view']);
        $this->assertSame(4, $indexSnapshot['payload']['count']);
        $this->assertSame([
            'catalog_product_price',
            'cataloginventory_stock',
            'catalog_url',
            'catalogsearch_fulltext',
        ], array_column($indexPayloads, 'process_code'));
        $this->assertSame(['reindex_required', 'ready', 'processing', 'error'], array_column($indexPayloads, 'status'));
        $this->assertSame(['require_reindex', 'pending', 'working', 'require_reindex'], array_column($indexPayloads, 'legacy_status'));
        $this->assertTrue($indexPayloads[0]['update_required']);
        $this->assertFalse($indexPayloads[1]['update_required']);
        $this->assertTrue($indexPayloads[2]['is_locked']);
        $this->assertSame('indexer:catalog_url:9001', $indexPayloads[2]['lock']['owner']);
        $this->assertSame('lock_wait_timeout', $indexPayloads[3]['failure_reason']);
        $this->assertSame('catalog_product_index_price_reindex_all', $indexPayloads[0]['cron']['job']);
        $this->assertSame('0 2 * * *', $indexPayloads[0]['cron']['schedule']);

        foreach ($indexPayloads as $indexPayload) {
            $this->assertArrayHasKey('operation_type', $indexPayload);
            $this->assertArrayHasKey('process_code', $indexPayload);
            $this->assertArrayHasKey('status', $indexPayload);
            $this->assertArrayHasKey('legacy_status', $indexPayload);
            $this->assertArrayHasKey('mode', $indexPayload);
            $this->assertArrayHasKey('update_required', $indexPayload);
            $this->assertArrayHasKey('is_stale', $indexPayload);
            $this->assertArrayHasKey('is_locked', $indexPayload);
            $this->assertArrayHasKey('event_count', $indexPayload);
            $this->assertArrayHasKey('ui', $indexPayload);
        }

        $deIndexSnapshot = $this->app->make(DomainQueryService::class)->snapshot('index', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $deIndexPayloads = array_column($deIndexSnapshot['payload']['rows'], 'payload');

        $this->assertSame('de', $deIndexSnapshot['store_view']);
        $this->assertSame(2, $deIndexSnapshot['payload']['count']);
        $this->assertSame(['catalog_product_price', 'catalogsearch_fulltext'], array_column($deIndexPayloads, 'process_code'));
        $this->assertSame(['ready', 'reindex_required'], array_column($deIndexPayloads, 'status'));
        $this->assertSame('DE search index requires reindex after query updates', $deIndexPayloads[1]['ui']['summary']);

        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'cache',
            'entity_id' => 10806,
            'store_id' => 9002,
            'store_view' => 'de',
        ]);
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'index',
            'entity_id' => 10906,
            'store_id' => 9002,
            'store_view' => 'de',
        ]);
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

    public function test_domain_fact_seeder_provides_cms_seo_and_store_scope_snapshots(): void
    {
        $this->seed(DomainFactSeeder::class);

        $cmsPageSnapshot = $this->app->make(DomainQueryService::class)->snapshot('cms_page', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $cmsPagePayloads = array_column($cmsPageSnapshot['payload']['rows'], 'payload');

        $this->assertSame('CmsPage', $cmsPageSnapshot['feature']['context']);
        $this->assertSame(3, $cmsPageSnapshot['payload']['count']);
        $this->assertSame(['about-us', 'spring-sale-legacy', 'no-route'], array_column($cmsPagePayloads, 'identifier'));
        $this->assertSame(['active', 'disabled', 'active'], array_column($cmsPagePayloads, 'status'));
        $this->assertSame([true, false, true], array_column($cmsPagePayloads, 'is_active'));
        $this->assertSame([false, false, true], array_column($cmsPagePayloads, 'is_404'));
        $this->assertSame('/sale', $cmsPagePayloads[1]['redirect']['target']);
        $this->assertSame(301, $cmsPagePayloads[1]['redirect']['type']);
        $this->assertSame('cms/index/noRoute', $cmsPagePayloads[2]['target_path']);
        $this->assertSame(['home-page-hero', 'footer-links'], $cmsPagePayloads[0]['block_identifiers']);
        $this->assertSame([10201], $cmsPagePayloads[0]['widget_instance_ids']);
        $this->assertSame('About Us | Example Store', $cmsPagePayloads[0]['meta_title']);

        foreach ($cmsPagePayloads as $cmsPagePayload) {
            $this->assertArrayHasKey('identifier', $cmsPagePayload);
            $this->assertArrayHasKey('status', $cmsPagePayload);
            $this->assertArrayHasKey('content_preview', $cmsPagePayload);
            $this->assertArrayHasKey('canonical_url', $cmsPagePayload);
            $this->assertArrayHasKey('redirect', $cmsPagePayload);
        }

        $deCmsPageSnapshot = $this->app->make(DomainQueryService::class)->snapshot('cms_page', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $deCmsPagePayloads = array_column($deCmsPageSnapshot['payload']['rows'], 'payload');

        $this->assertSame('de', $deCmsPageSnapshot['store_view']);
        $this->assertSame(3, $deCmsPageSnapshot['payload']['count']);
        $this->assertSame(['ueber-uns', 'fruehlingsaktion-alt', 'no-route-de'], array_column($deCmsPagePayloads, 'identifier'));
        $this->assertSame('/de/angebote', $deCmsPagePayloads[1]['redirect']['target']);
        $this->assertTrue($deCmsPagePayloads[2]['is_no_route']);
        $this->assertSame('Seite Nicht Gefunden', $deCmsPagePayloads[2]['title']);

        $cmsBlockSnapshot = $this->app->make(DomainQueryService::class)->snapshot('cms_block', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $cmsBlockPayloads = array_column($cmsBlockSnapshot['payload']['rows'], 'payload');

        $this->assertSame('CmsBlock', $cmsBlockSnapshot['feature']['context']);
        $this->assertSame(2, $cmsBlockSnapshot['payload']['count']);
        $this->assertSame(['home-page-hero', 'footer-links'], array_column($cmsBlockPayloads, 'identifier'));
        $this->assertSame(['wysiwyg/home/default-hero.jpg'], $cmsBlockPayloads[0]['wysiwyg_media']);
        $this->assertSame(['cms_page:about-us'], $cmsBlockPayloads[0]['used_on']);
        $this->assertContains('store_9001', $cmsBlockPayloads[0]['cache_tags']);

        $deCmsBlockSnapshot = $this->app->make(DomainQueryService::class)->snapshot('cms_block', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $deCmsBlockPayloads = array_column($deCmsBlockSnapshot['payload']['rows'], 'payload');

        $this->assertSame(2, $deCmsBlockSnapshot['payload']['count']);
        $this->assertSame(['home-page-hero-de', 'footer-links-de'], array_column($deCmsBlockPayloads, 'identifier'));
        $this->assertSame('Startseiten-Hero', $deCmsBlockPayloads[0]['title']);

        $widgetSnapshot = $this->app->make(DomainQueryService::class)->snapshot('widget', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $widgetPayloads = array_column($widgetSnapshot['payload']['rows'], 'payload');

        $this->assertSame('Widget', $widgetSnapshot['feature']['context']);
        $this->assertSame(2, $widgetSnapshot['payload']['count']);
        $this->assertSame(['catalog/widget_new', 'cms/widget_block'], array_column($widgetPayloads, 'type'));
        $this->assertSame([true, false], array_column($widgetPayloads, 'is_active'));
        $this->assertSame(['simple-shirt', 'configurable-hoodie'], $widgetPayloads[0]['conditions']['skus']);
        $this->assertSame('footer-links', $widgetPayloads[1]['conditions']['block_identifier']);

        $deWidgetSnapshot = $this->app->make(DomainQueryService::class)->snapshot('widget', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $deWidgetPayloads = array_column($deWidgetSnapshot['payload']['rows'], 'payload');

        $this->assertSame(2, $deWidgetSnapshot['payload']['count']);
        $this->assertSame('Neue Produkte', $deWidgetPayloads[0]['title']);
        $this->assertSame('footer-links-de', $deWidgetPayloads[1]['conditions']['block_identifier']);

        $sitemapSnapshot = $this->app->make(DomainQueryService::class)->snapshot('sitemap', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $sitemapPayloads = array_column($sitemapSnapshot['payload']['rows'], 'payload');

        $this->assertSame('Sitemap', $sitemapSnapshot['feature']['context']);
        $this->assertSame(2, $sitemapSnapshot['payload']['count']);
        $this->assertSame(['sitemap', 'rss'], array_column($sitemapPayloads, 'type'));
        $this->assertSame([true, false], array_column($sitemapPayloads, 'is_fresh'));
        $this->assertSame('/sitemap.xml', $sitemapPayloads[0]['url']);
        $this->assertSame('/rss/catalog/new', $sitemapPayloads[0]['rss']['feed_url']);
        $this->assertTrue($sitemapPayloads[0]['rss']['is_fresh']);
        $this->assertSame('catalog price rule updated after feed generation', $sitemapPayloads[1]['stale_reason']);

        $deSitemapSnapshot = $this->app->make(DomainQueryService::class)->snapshot('sitemap', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $deSitemapPayloads = array_column($deSitemapSnapshot['payload']['rows'], 'payload');

        $this->assertSame(2, $deSitemapSnapshot['payload']['count']);
        $this->assertSame('/de/sitemap.xml', $deSitemapPayloads[0]['url']);
        $this->assertSame('localized CMS page updated after feed generation', $deSitemapPayloads[1]['stale_reason']);

        $urlRewriteSnapshot = $this->app->make(DomainQueryService::class)->snapshot('url_rewrite', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $urlRewritePayloads = array_column($urlRewriteSnapshot['payload']['rows'], 'payload');

        $this->assertSame('UrlRewrite', $urlRewriteSnapshot['feature']['context']);
        $this->assertSame(2, $urlRewriteSnapshot['payload']['count']);
        $this->assertSame(['about-us', 'company'], array_column($urlRewritePayloads, 'request_path'));
        $this->assertSame([0, 301], array_column($urlRewritePayloads, 'redirect_type'));
        $this->assertSame('/about-us', $urlRewritePayloads[0]['canonical_url']);
        $this->assertSame('admin_redirect', $urlRewritePayloads[1]['metadata']['source']);
        $this->assertTrue($urlRewritePayloads[1]['metadata']['preserve_query']);

        $deUrlRewriteSnapshot = $this->app->make(DomainQueryService::class)->snapshot('url_rewrite', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $deUrlRewritePayloads = array_column($deUrlRewriteSnapshot['payload']['rows'], 'payload');

        $this->assertSame(2, $deUrlRewriteSnapshot['payload']['count']);
        $this->assertSame(['de/ueber-uns', 'de/unternehmen'], array_column($deUrlRewritePayloads, 'request_path'));
        $this->assertSame('/de/ueber-uns', $deUrlRewritePayloads[1]['canonical_url']);
        $this->assertSame('localized cms identifier changed', $deUrlRewritePayloads[1]['metadata']['reason']);

        $storeScopeSnapshot = $this->app->make(DomainQueryService::class)->snapshot('store_scope', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $storeScopePayload = $storeScopeSnapshot['payload']['rows'][0]['payload'];

        $this->assertSame('StoreScope', $storeScopeSnapshot['feature']['context']);
        $this->assertSame(1, $storeScopeSnapshot['payload']['count']);
        $this->assertSame('default', $storeScopePayload['store_code']);
        $this->assertSame('en_US', $storeScopePayload['locale']);
        $this->assertSame('no-route', $storeScopePayload['cms_no_route']);
        $this->assertTrue($storeScopePayload['config']['web/seo/use_rewrites']);
        $this->assertTrue($storeScopePayload['config']['rss/config/active']);

        $deStoreScopeSnapshot = $this->app->make(DomainQueryService::class)->snapshot('store_scope', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $deStoreScopePayload = $deStoreScopeSnapshot['payload']['rows'][0]['payload'];

        $this->assertSame(1, $deStoreScopeSnapshot['payload']['count']);
        $this->assertSame('de', $deStoreScopePayload['store_code']);
        $this->assertSame('de_DE', $deStoreScopePayload['locale']);
        $this->assertSame('no-route-de', $deStoreScopePayload['cms_no_route']);
        $this->assertFalse($deStoreScopePayload['can_use_default']);

        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'cms_page',
            'entity_id' => 10006,
            'store_id' => 9002,
            'store_view' => 'de',
        ]);
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'cms_block',
            'entity_id' => 10104,
            'store_id' => 9002,
            'store_view' => 'de',
        ]);
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'widget',
            'entity_id' => 10204,
            'store_id' => 9002,
            'store_view' => 'de',
        ]);
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'sitemap',
            'entity_id' => 10304,
            'store_id' => 9002,
            'store_view' => 'de',
        ]);
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'url_rewrite',
            'entity_id' => 10404,
            'store_id' => 9002,
            'store_view' => 'de',
        ]);
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'store_scope',
            'entity_id' => 10502,
            'store_id' => 9002,
            'store_view' => 'de',
        ]);
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

        $wishlistSnapshot = $this->app->make(DomainQueryService::class)->snapshot('wishlist', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $wishlistPayloads = array_column($wishlistSnapshot['payload']['rows'], 'payload');

        $this->assertSame('Wishlist', $wishlistSnapshot['feature']['context']);
        $this->assertSame('default', $wishlistSnapshot['store_view']);
        $this->assertSame(2, $wishlistSnapshot['payload']['count']);
        $this->assertSame([5001, 5001], array_column($wishlistPayloads, 'customer_id'));
        $this->assertSame(['shared', 'empty_private'], array_column($wishlistPayloads, 'state'));
        $this->assertSame('wl-default-maria-001', $wishlistPayloads[0]['sharing_code']);
        $this->assertSame(['simple-shirt', 'configurable-hoodie'], $wishlistPayloads[0]['visible_product_skus']);
        $this->assertSame('simple-shirt', $wishlistPayloads[0]['items'][0]['sku']);
        $this->assertTrue($wishlistPayloads[0]['can_move_to_cart']);
        $this->assertSame([], $wishlistPayloads[1]['visible_product_skus']);
        $this->assertSame([], $wishlistPayloads[1]['items']);
        $this->assertFalse($wishlistPayloads[1]['can_move_to_cart']);
        $this->assertSame('not_shared', $wishlistPayloads[1]['denied_reason']);

        $deWishlistSnapshot = $this->app->make(DomainQueryService::class)->snapshot('wishlist', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $this->assertSame('de', $deWishlistSnapshot['store_view']);
        $this->assertSame(1, $deWishlistSnapshot['payload']['count']);
        $this->assertSame(5002, $deWishlistSnapshot['payload']['rows'][0]['payload']['customer_id']);
        $this->assertSame(['simple-shirt'], $deWishlistSnapshot['payload']['rows'][0]['payload']['visible_product_skus']);
        $this->assertSame('Sichere geteilte Wunschlisten-Fixture.', $deWishlistSnapshot['payload']['rows'][0]['payload']['message']);

        $compareSnapshot = $this->app->make(DomainQueryService::class)->snapshot('compare', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $comparePayloads = array_column($compareSnapshot['payload']['rows'], 'payload');

        $this->assertSame('Compare', $compareSnapshot['feature']['context']);
        $this->assertSame('default', $compareSnapshot['store_view']);
        $this->assertSame(2, $compareSnapshot['payload']['count']);
        $this->assertSame(['active', 'empty_guest'], array_column($comparePayloads, 'state'));
        $this->assertSame(['simple-shirt', 'configurable-hoodie'], $comparePayloads[0]['visible_product_skus']);
        $this->assertSame('price', $comparePayloads[0]['attributes'][0]['code']);
        $this->assertSame(29.95, $comparePayloads[0]['attributes'][0]['values']['simple-shirt']);
        $this->assertSame('Color', $comparePayloads[0]['attributes'][1]['label']);
        $this->assertSame([], $comparePayloads[1]['visible_product_skus']);
        $this->assertSame([], $comparePayloads[1]['attributes']);
        $this->assertSame('guest_session_expired', $comparePayloads[1]['denied_reason']);

        $deCompareSnapshot = $this->app->make(DomainQueryService::class)->snapshot('compare', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $this->assertSame('de', $deCompareSnapshot['store_view']);
        $this->assertSame(1, $deCompareSnapshot['payload']['count']);
        $this->assertSame(5002, $deCompareSnapshot['payload']['rows'][0]['payload']['customer_id']);
        $this->assertSame('Preis', $deCompareSnapshot['payload']['rows'][0]['payload']['attributes'][0]['label']);
        $this->assertSame('Farbe', $deCompareSnapshot['payload']['rows'][0]['payload']['attributes'][1]['label']);

        $reviewSnapshot = $this->app->make(DomainQueryService::class)->snapshot('review', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $reviewPayloads = array_column($reviewSnapshot['payload']['rows'], 'payload');

        $this->assertSame('Review', $reviewSnapshot['feature']['context']);
        $this->assertSame('default', $reviewSnapshot['store_view']);
        $this->assertSame(2, $reviewSnapshot['payload']['count']);
        $this->assertSame(['simple-shirt', 'configurable-hoodie'], array_column($reviewPayloads, 'product_sku'));
        $this->assertSame([5001, 5001], array_column($reviewPayloads, 'customer_id'));
        $this->assertSame(['approved', 'pending'], array_column($reviewPayloads, 'status'));
        $this->assertTrue($reviewPayloads[0]['is_visible']);
        $this->assertFalse($reviewPayloads[1]['is_visible']);
        $this->assertSame('approved', $reviewPayloads[0]['moderation']['status']);
        $this->assertSame('pending', $reviewPayloads[1]['moderation']['status']);
        $this->assertSame(5, $reviewPayloads[0]['ratings']['quality']);
        $this->assertSame(4.3, $reviewPayloads[0]['average_rating']);

        $deReviewSnapshot = $this->app->make(DomainQueryService::class)->snapshot('review', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $this->assertSame('de', $deReviewSnapshot['store_view']);
        $this->assertSame(1, $deReviewSnapshot['payload']['count']);
        $this->assertSame(5002, $deReviewSnapshot['payload']['rows'][0]['payload']['customer_id']);
        $this->assertSame('Zuverlaessiges Hemd', $deReviewSnapshot['payload']['rows'][0]['payload']['title']);
        $this->assertSame(4.7, $deReviewSnapshot['payload']['rows'][0]['payload']['average_rating']);

        $tagSnapshot = $this->app->make(DomainQueryService::class)->snapshot('tag', [
            'store_id' => 9001,
            'store_view' => 'default',
        ]);

        $tagPayloads = array_column($tagSnapshot['payload']['rows'], 'payload');

        $this->assertSame('Tag', $tagSnapshot['feature']['context']);
        $this->assertSame('default', $tagSnapshot['store_view']);
        $this->assertSame(2, $tagSnapshot['payload']['count']);
        $this->assertSame(['simple-shirt', 'configurable-hoodie'], array_column($tagPayloads, 'product_sku'));
        $this->assertSame([5001, 5001], array_column($tagPayloads, 'customer_id'));
        $this->assertSame(['approved', 'pending'], array_column($tagPayloads, 'status'));
        $this->assertTrue($tagPayloads[0]['is_visible']);
        $this->assertFalse($tagPayloads[1]['is_visible']);
        $this->assertSame('summer', $tagPayloads[0]['name']);
        $this->assertSame(['simple-shirt'], $tagPayloads[0]['related_product_skus']);

        $deTagSnapshot = $this->app->make(DomainQueryService::class)->snapshot('tag', [
            'store_id' => 9002,
            'store_view' => 'de',
        ]);

        $this->assertSame('de', $deTagSnapshot['store_view']);
        $this->assertSame(1, $deTagSnapshot['payload']['count']);
        $this->assertSame(5002, $deTagSnapshot['payload']['rows'][0]['payload']['customer_id']);
        $this->assertSame('sommer', $deTagSnapshot['payload']['rows'][0]['payload']['name']);
        $this->assertSame('approved', $deTagSnapshot['payload']['rows'][0]['payload']['status']);

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
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'wishlist',
            'entity_id' => 9103,
            'store_id' => 9002,
            'store_view' => 'de',
        ]);
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'compare',
            'entity_id' => 9203,
            'store_id' => 9002,
            'store_view' => 'de',
        ]);
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'review',
            'entity_id' => 9303,
            'store_id' => 9002,
            'store_view' => 'de',
        ]);
        $this->assertDatabaseHas('domain_facts', [
            'feature_key' => 'tag',
            'entity_id' => 9403,
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
            'AD-011',
            'AD-013',
            'AD-015',
            'AD-017',
            'CB-012',
            'CB-013',
            'CJ-016',
            'CJ-019',
            'CJ-021',
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
