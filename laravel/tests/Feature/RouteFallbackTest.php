<?php

namespace Tests\Feature;

use App\Modernization\Routing\RouteOwner;
use App\Modernization\Routing\RouteOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RouteFallbackTest extends TestCase
{
    public function test_route_ownership_metadata_marks_modernization_routes_as_laravel_owned(): void
    {
        $decision = $this->app
            ->make(RouteOwnership::class)
            ->resolve(Request::create('/_modernization/modules', 'GET'));

        $this->assertSame(RouteOwner::Laravel, $decision->owner);
        $this->assertSame('ARCH', $decision->feature);
        $this->assertSame('legacy_disabled', $decision->rollback);
    }

    public function test_unmigrated_store_scope_url_rewrite_falls_back_to_legacy_when_feature_flag_enabled(): void
    {
        Log::spy();

        config([
            'route_ownership.fallback.enabled' => true,
            'route_ownership.legacy_base_url' => 'https://legacy.example.test',
        ]);

        $response = $this->get('/de/catalog/product/view/id/100?color=red');

        $response
            ->assertStatus(307)
            ->assertRedirect('https://legacy.example.test/de/catalog/product/view/id/100?color=red')
            ->assertHeader('X-Route-Owner', 'legacy')
            ->assertHeader('X-Store-Code', 'de')
            ->assertHeader('X-Route-Fallback-Enabled', 'true');

        $this->assertSame('de', $response->headers->get('X-Store-Code'), 'store code URL rewrite metadata is preserved');

        Log::shouldHaveReceived('withContext')->once();
        Log::shouldHaveReceived('info')->once()->with('Route fallback decision recorded');
    }

    public function test_admin_frontname_and_admin_session_boundary_are_reported_for_disabled_legacy_fallback(): void
    {
        $response = $this
            ->withSession(['admin_user_id' => 42])
            ->get('/admin/catalog_product/index');

        $response
            ->assertStatus(503)
            ->assertHeader('X-Route-Owner', 'bridge')
            ->assertHeader('X-Admin-Frontname', 'true')
            ->assertJsonPath('route.admin_frontname', true)
            ->assertJsonPath('session.admin', true)
            ->assertJsonPath('session.boundary.admin', 'explicit_non_sharing');
    }

    public function test_csrf_form_key_compatibility_and_customer_session_boundary_are_observed_without_proxying(): void
    {
        $response = $this
            ->withSession(['customer_id' => 7])
            ->post('/checkout/cart/add', ['form_key' => 'abc123']);

        $response
            ->assertStatus(503)
            ->assertJsonPath('csrf.form_key_present', true)
            ->assertJsonPath('session.customer', true)
            ->assertJsonPath('session.boundary.customer', 'explicit_non_sharing');
    }

    public function test_rollback_feature_flag_keeps_laravel_owned_routes_out_of_legacy_fallback(): void
    {
        config([
            'route_ownership.fallback.enabled' => true,
            'route_ownership.routes' => [
                [
                    'pattern' => 'catalogsearch/*',
                    'methods' => ['GET'],
                    'owner' => 'Laravel',
                    'feature' => 'CATALOG_SEARCH',
                    'feature_flag' => 'route.catalogsearch.laravel',
                    'rollback' => 'legacy_catalogsearch',
                ],
            ],
        ]);

        $response = $this->get('/catalogsearch/result');

        $response
            ->assertNotFound()
            ->assertHeader('X-Route-Owner', 'Laravel')
            ->assertJsonPath('route.feature_flag', 'route.catalogsearch.laravel')
            ->assertJsonPath('route.rollback', 'legacy_catalogsearch');
    }
}
