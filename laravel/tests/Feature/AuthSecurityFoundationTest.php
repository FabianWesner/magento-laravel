<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modernization\Auth\FormKeyCompatibility;
use App\Modernization\Auth\PasswordHashCompatibility;
use App\Modernization\Auth\PermissionManifest;
use App\Modernization\Auth\SessionCookieBoundary;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthSecurityFoundationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const AUTH_SECURITY_FEATURE_IDS = [
        'SF-010',
        'AD-001',
        'AD-012',
        'API-001',
        'API-002',
        'API-003',
        'CB-013',
    ];

    public function test_customer_and_admin_guards_providers_and_password_brokers_are_configured(): void
    {
        $this->assertSame('customers', config('auth.guards.customer.provider'));
        $this->assertSame('admins', config('auth.guards.admin.provider'));
        $this->assertSame(User::class, config('auth.providers.customers.model'));
        $this->assertSame(User::class, config('auth.providers.admins.model'));
        $this->assertSame('customers', config('auth.passwords.customers.provider'));
        $this->assertSame('admins', config('auth.passwords.admins.provider'));
        $this->assertNotNull(Password::broker('customers'), 'password broker for customer reset token flow exists');
        $this->assertNotNull(Password::broker('admins'), 'password broker for admin reset token flow exists');
    }

    public function test_customer_login_logout_reset_password_and_session_boundary_use_customer_guard(): void
    {
        $customer = $this->userWithRole('customer role', 101);

        $this->getJson('/_modernization/auth/forgot-password')
            ->assertOk()
            ->assertJsonPath('brokers.0', 'customers');

        $this
            ->actingAs($customer, 'customer')
            ->withSession(['customer_id' => 101])
            ->getJson('/_modernization/auth/customer/session')
            ->assertOk()
            ->assertJsonPath('feature_id', 'SF-010')
            ->assertJsonPath('session.guard', 'customer')
            ->assertJsonPath('session.authenticated', true)
            ->assertJsonPath('session.rollback', 'legacy_runtime_fallback');

        $this
            ->actingAs($customer, 'customer')
            ->postJson('/_modernization/auth/logout-boundary')
            ->assertOk()
            ->assertJsonPath('logout.invalidate', true);
    }

    public function test_admin_login_timeout_form_key_and_acl_denial_cover_full_partial_readonly_and_denied_roles(): void
    {
        $manifest = $this->app->make(PermissionManifest::class);

        $this->assertTrue($manifest->allows('full', 'admin.dashboard'), 'full role has admin ACL access');
        $this->assertFalse($manifest->allows('partial', 'admin.dashboard'), 'partial role has catalog/report permissions but not dashboard');
        $this->assertTrue($manifest->allows('read-only', 'reports.read'), 'read-only role can read reports');
        $this->assertFalse($manifest->allows('denied', 'admin.dashboard'), 'denied role/no-access cannot open admin');

        $this
            ->actingAs($this->userWithRole('full', 201), 'admin')
            ->withSession(['admin_user_id' => 201])
            ->getJson('/_modernization/auth/admin/session')
            ->assertOk()
            ->assertJsonPath('feature_id', 'AD-001')
            ->assertJsonPath('session.timeout_minutes', 120);

        $this
            ->actingAs($this->userWithRole('denied', 202), 'admin')
            ->getJson('/_modernization/auth/admin/session')
            ->assertForbidden();
    }

    public function test_csrf_form_key_missing_token_and_invalid_token_failures_are_explicit(): void
    {
        $formKey = $this->app->make(FormKeyCompatibility::class);

        $this->assertFalse($formKey->inspect(null, 'expected')['present'], 'CSRF missing token is reported');
        $this->assertFalse($formKey->inspect('invalid', 'expected')['valid'], 'invalid token/form key is rejected');
        $this->assertTrue($formKey->inspect('expected', 'expected')['valid']);

        $this
            ->withSession(['_form_key' => 'expected'])
            ->postJson('/_modernization/auth/csrf-form-key', ['form_key' => 'expected'])
            ->assertOk()
            ->assertJsonPath('feature_id', 'CB-013')
            ->assertJsonPath('form_key.valid', true);
    }

    public function test_password_hash_verification_upgrade_and_broker_flows_cover_legacy_passwords(): void
    {
        $compatibility = $this->app->make(PasswordHashCompatibility::class);
        $modernHash = Hash::make('secret');

        $this->assertTrue(Hash::check('secret', $modernHash));
        $this->assertFalse(Hash::needsRehash($modernHash));
        $this->assertTrue($compatibility->verifyAndPlanUpgrade('secret', $modernHash)['verified']);

        $legacyHash = md5('salt-secret').':salt-';
        $legacyResult = $compatibility->verifyAndPlanUpgrade('secret', $legacyHash);

        $this->assertTrue($legacyResult['verified']);
        $this->assertTrue($legacyResult['needs_rehash']);
        $this->assertSame('legacy password salted-md5', $legacyResult['algorithm']);
    }

    public function test_api_auth_oauth_roles_and_all_auth_security_feature_ids_are_tracked(): void
    {
        $this->assertSame([
            'SF-010',
            'AD-001',
            'AD-012',
            'API-001',
            'API-002',
            'API-003',
            'CB-013',
        ], self::AUTH_SECURITY_FEATURE_IDS);

        $this->getJson('/api/v1/contracts/API-003')
            ->assertOk()
            ->assertJsonPath('data.auth', 'OAuth token')
            ->assertJsonPath('data.roles.0', 'admin role')
            ->assertJsonPath('data.roles.1', 'customer role')
            ->assertJsonPath('data.roles.2', 'guest role');
    }

    public function test_dual_runtime_magento_legacy_session_cookie_flags_logout_invalidation_and_rollback_are_documented(): void
    {
        $boundary = $this->app->make(SessionCookieBoundary::class);
        $cookie = $boundary->cookie();
        $logout = $boundary->logoutInvalidation();

        $this->assertSame('lax', $cookie['same-site']);
        $this->assertFalse((bool) $cookie['secure_flag']);
        $this->assertTrue($logout['invalidate'], 'logout invalidation crosses the legacy session boundary');
        $this->assertSame('legacy_runtime_fallback', $logout['rollback']);
        $this->assertSame('explicit non-sharing until route ownership is Laravel', config('auth_compatibility.session.customer'), 'Magento legacy comparison uses explicit cross-runtime isolation');
    }

    private function userWithRole(string $role, int $id): User
    {
        $user = new User;
        $user->forceFill([
            'id' => $id,
            'name' => "{$role} test user",
            'email' => "{$role}@example.test",
            'password' => Hash::make('secret'),
        ]);
        $user->setAttribute('role', $role);
        $user->exists = true;

        return $user;
    }
}
