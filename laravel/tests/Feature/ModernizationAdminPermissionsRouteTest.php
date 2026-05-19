<?php

namespace Tests\Feature;

use App\Livewire\AdminPermissionsWorkbench;
use App\Modernization\Auth\AdminPermissionCatalog;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationAdminPermissionsRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_permissions_route_renders_livewire_workbench(): void
    {
        $response = $this->get(route('modernization.admin.admin-permissions'));

        $response
            ->assertOk()
            ->assertSee('Modernization Admin Permissions')
            ->assertSee(route('modernization.assets.admin-permissions'))
            ->assertSee('Admin permissions workbench')
            ->assertSee('AD-001, AD-012, API-001 through API-003')
            ->assertSee('4 admin roles')
            ->assertSee('5 ACL resources')
            ->assertSee('5 API permissions')
            ->assertSee('7 allowed rows')
            ->assertSee('7 attention rows')
            ->assertSee('Full Access')
            ->assertSee('Denied Admin');

        $this->get(route('modernization.assets.admin-permissions'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8');
    }

    public function test_admin_permission_catalog_combines_roles_resources_and_api_permissions(): void
    {
        $catalog = $this->app->make(AdminPermissionCatalog::class);
        $rows = $catalog->rows();

        $this->assertCount(14, $rows);
        $this->assertSame([
            'admin_roles' => 4,
            'acl_resources' => 5,
            'api_permissions' => 5,
            'allowed_rows' => 7,
            'attention' => 7,
        ], $catalog->summary($rows));

        $this->assertSame(['api_oauth', 'classic_soap_xmlrpc', 'api2_admin_role', 'api2_customer_role', 'api2_guest_role', 'oauth_consumers_tokens'], array_column($catalog->filtered(['status' => 'contract-test-required']), 'key'));
        $this->assertSame(['classic_soap_xmlrpc', 'api2_admin_role', 'oauth_consumers_tokens'], array_column($catalog->filtered(['role' => 'admin']), 'key'));
        $this->assertSame(['api2_guest_role'], array_column($catalog->filtered(['role' => 'guest']), 'key'));
        $this->assertSame(['denied'], array_column($catalog->filtered(['status' => 'denied']), 'key'));
        $this->assertCount(6, $catalog->filtered(['feature_id' => 'API-003']));
    }

    public function test_admin_permissions_livewire_filters_sections_empty_and_denied_states(): void
    {
        Livewire::test(AdminPermissionsWorkbench::class)
            ->assertSee('4 admin roles')
            ->assertSee('Full Access')
            ->assertSee('Denied Admin')
            ->call('setSection', 'resources')
            ->assertSee('5 ACL resources')
            ->assertSee('Admin Dashboard')
            ->assertSee('Reports Read')
            ->call('setSection', 'api')
            ->assertSee('5 API permissions')
            ->assertSee('Classic SOAP/XML-RPC API User')
            ->assertSee('REST/API2 Guest Role')
            ->assertSee('OAuth Consumers And Tokens')
            ->call('setSection', 'problems')
            ->assertSee('7 attention rows')
            ->assertSee('Denied Admin')
            ->assertSee('API OAuth Permission')
            ->assertSee('Classic SOAP/XML-RPC API User')
            ->set('featureId', 'API-003')
            ->assertSee('5 attention rows')
            ->assertSee('REST/API2 Guest Role')
            ->assertSee('OAuth Consumers And Tokens')
            ->set('query', 'not-present')
            ->assertSee('There are no problems rows matching this admin permissions scope.')
            ->call('clearFilters')
            ->call('setSection', 'api')
            ->set('subjectRole', 'guest')
            ->assertSee('1 API permissions')
            ->assertSee('REST/API2 Guest Role')
            ->call('clearFilters')
            ->set('viewerRole', 'denied')
            ->assertSee('Permission denied for admin permissions viewer role `denied`.')
            ->assertDontSee('Full Access');
    }

    public function test_admin_permissions_livewire_status_kind_and_read_only_viewer_filters(): void
    {
        Livewire::test(AdminPermissionsWorkbench::class)
            ->set('viewerRole', 'read-only')
            ->assertSee('Full Access')
            ->assertDontSee('Permission denied')
            ->call('setSection', 'resources')
            ->set('status', 'read-only')
            ->assertSee('1 ACL resources')
            ->assertSee('Reports Read')
            ->assertDontSee('Admin Dashboard')
            ->set('status', '')
            ->set('kind', 'api_permission')
            ->assertSee('0 ACL resources')
            ->assertSee('There are no resources rows matching this admin permissions scope.')
            ->call('setSection', 'api')
            ->assertSee('5 API permissions')
            ->assertSee('REST/API2 Admin Role')
            ->set('status', 'attention')
            ->assertSee('5 API permissions')
            ->assertSee('OAuth Consumers And Tokens');
    }

    public function test_admin_permissions_livewire_normalizes_invalid_public_filter_state(): void
    {
        Livewire::test(AdminPermissionsWorkbench::class)
            ->set('status', 'bad-status')
            ->set('kind', 'bad-kind')
            ->set('featureId', 'bad-feature')
            ->set('subjectRole', 'bad-role')
            ->set('section', 'bad-section')
            ->set('viewerRole', 'bad-viewer')
            ->assertSet('status', '')
            ->assertSet('kind', '')
            ->assertSet('featureId', '')
            ->assertSet('subjectRole', '')
            ->assertSet('section', 'roles')
            ->assertSet('viewerRole', 'security')
            ->assertSee('4 admin roles');
    }
}
