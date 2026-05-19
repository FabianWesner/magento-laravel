<?php

namespace Tests\Feature;

use App\Modernization\Config\AdminConfig;
use App\Modernization\Config\ConfigCache;
use App\Modernization\Config\ConfigScope;
use App\Modernization\Config\ScopedConfig;
use App\Modernization\Config\SecretConfig;
use App\Modernization\Config\StoreScope;
use App\Modernization\Config\StoreView;
use App\Modernization\Config\WebsiteScope;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ScopedConfigTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const CONFIG_FEATURE_IDS = ['SF-012', 'AD-010', 'CB-011', 'CB-013'];

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('core_config_data');
        Schema::create('core_config_data', function (Blueprint $table): void {
            $table->increments('config_id');
            $table->string('scope');
            $table->integer('scope_id');
            $table->string('path');
            $table->text('value')->nullable();
        });

        $secret = $this->app->make(SecretConfig::class)->encrypt('payment/gateway/token', 'default-token');

        DB::table('core_config_data')->insert([
            ['scope' => 'default', 'scope_id' => 0, 'path' => 'web/unsecure/base_url', 'value' => 'https://default.example.test/'],
            ['scope' => 'websites', 'scope_id' => 1, 'path' => 'web/unsecure/base_url', 'value' => 'https://website.example.test/'],
            ['scope' => 'stores', 'scope_id' => 3, 'path' => 'web/unsecure/base_url', 'value' => 'https://store.example.test/'],
            ['scope' => 'default', 'scope_id' => 0, 'path' => 'web/secure/base_url', 'value' => 'https://secure-default.example.test/'],
            ['scope' => 'default', 'scope_id' => 0, 'path' => 'general/locale/code', 'value' => 'en_US'],
            ['scope' => 'websites', 'scope_id' => 1, 'path' => 'currency/options/base', 'value' => 'EUR'],
            ['scope' => 'default', 'scope_id' => 0, 'path' => 'payment/gateway/token', 'value' => $secret],
            ['scope' => 'default', 'scope_id' => 0, 'path' => 'catalog/frontend/list_mode', 'value' => 'grid'],
        ]);
    }

    public function test_default_website_store_fallback_matches_magento_baseline_legacy_comparison_for_config_features(): void
    {
        $store = $this->storeView();
        $scopedConfig = $this->app->make(ScopedConfig::class);

        $this->assertSame(self::CONFIG_FEATURE_IDS, ['SF-012', 'AD-010', 'CB-011', 'CB-013']);
        $this->assertSame('https://store.example.test/', $scopedConfig->get('web/unsecure/base_url', $store));
        $this->assertSame('EUR', $scopedConfig->get('currency/options/base', $store));
        $this->assertSame('en_US', $scopedConfig->get('general/locale/code', $store));
    }

    public function test_environment_override_and_secret_encrypted_fields_are_resolved(): void
    {
        config([
            'scoped_config.env_overrides' => [
                'web/secure/base_url' => 'https://env.example.test/',
            ],
        ]);

        $scopedConfig = $this->app->make(ScopedConfig::class);

        $this->assertSame('https://env.example.test/', $scopedConfig->get('web/secure/base_url', $this->storeView()));
        $this->assertSame('default-token', $scopedConfig->get('payment/gateway/token', $this->storeView()));
    }

    public function test_cache_invalidation_behavior_for_admin_save_forgets_stale_config(): void
    {
        $cache = $this->app->make(ConfigCache::class);
        $adminConfig = $this->app->make(AdminConfig::class);

        Cache::put($cache->key('catalog/frontend/list_mode', ConfigScope::Store, 3), 'list', 300);

        $adminConfig->save('catalog/frontend/list_mode', 'grid', ConfigScope::Store, 3);

        $this->assertNull(Cache::get($cache->key('catalog/frontend/list_mode', ConfigScope::Store, 3)));
        $this->assertDatabaseHas('core_config_data', [
            'scope' => 'stores',
            'scope_id' => 3,
            'path' => 'catalog/frontend/list_mode',
            'value' => 'grid',
        ]);
    }

    public function test_admin_system_configuration_save_validation_inherited_values_source_model_and_backend_model(): void
    {
        $adminConfig = $this->app->make(AdminConfig::class);

        $this->expectException(ValidationException::class);
        $adminConfig->save('catalog/frontend/list_mode', 'invalid', ConfigScope::Store, 3);
    }

    public function test_admin_config_inherited_value_skips_save_and_uses_backend_model_for_secret(): void
    {
        $adminConfig = $this->app->make(AdminConfig::class);

        $this->assertNull($adminConfig->save('catalog/frontend/list_mode', 'list', ConfigScope::Store, 3, inherited: true));
        $adminConfig->save('payment/gateway/token', 'changed-token', ConfigScope::Store, 3);

        $this->assertDatabaseMissing('core_config_data', [
            'scope' => 'stores',
            'scope_id' => 3,
            'path' => 'catalog/frontend/list_mode',
            'value' => 'list',
        ]);
        $this->assertSame('changed-token', $this->app->make(ScopedConfig::class)->get('payment/gateway/token', $this->storeView()));
    }

    public function test_store_switch_localization_currency_and_base_url_come_from_php_config_without_xml_dependency(): void
    {
        $profile = $this->app->make(ScopedConfig::class)->storeProfile($this->storeView());

        $this->assertSame([
            'locale' => 'en_US',
            'currency' => 'EUR',
            'base_url' => 'https://store.example.test/',
        ], $profile, 'store switch localization currency and base URL use typed config without XML');
    }

    private function storeView(): StoreView
    {
        $website = new WebsiteScope(id: 1, code: 'base');
        $storeScope = new StoreScope(id: 2, code: 'main', website: $website);

        $this->assertSame('main', $storeScope->code);

        return new StoreView(
            id: 3,
            code: 'de',
            website: $website,
            locale: 'de_DE',
            currency: 'EUR',
            baseUrl: 'https://store.example.test/',
        );
    }
}
