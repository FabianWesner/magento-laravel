<?php

namespace Tests\Feature;

use App\Modernization\Modules\Contracts\ModuleRegistryContract;
use App\Modernization\Modules\ModuleRegistry;
use App\Policies\Modernization\Modules\ModuleRegistryPolicy;
use Tests\TestCase;

class ModernizationModuleRegistryTest extends TestCase
{
    public function test_module_registry_resolves_from_php_config(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);
        $foundation = $registry->find('foundation');

        $this->assertTrue($registry->healthy());
        $this->assertNotNull($foundation);
        $this->assertSame('Laravel Foundation', $foundation->name);
        $this->assertSame(['ARCH'], $foundation->featureIds);
        $this->assertSame([], $registry->errors());
        $this->assertSame($registry, $this->app->make(ModuleRegistryContract::class));
    }

    public function test_module_registry_diagnostic_route_returns_health_and_manifests(): void
    {
        $response = $this->get('/_modernization/modules');

        $response
            ->assertOk()
            ->assertJsonPath('health.status', 'ok')
            ->assertJsonPath('health.module_count', 1)
            ->assertJsonPath('health.enabled_module_count', 1)
            ->assertJsonPath('registry.modules.0.id', 'foundation')
            ->assertJsonPath('registry.modules.0.feature_ids.0', 'ARCH');
    }

    public function test_module_registry_console_diagnostic_lists_enabled_modules(): void
    {
        $this->artisan('modernization:modules')
            ->expectsOutput('foundation | Laravel Foundation | 0.1.0')
            ->assertSuccessful();
    }

    public function test_module_registry_reports_invalid_manifest_configuration(): void
    {
        $registry = ModuleRegistry::fromConfig([
            [
                'id' => 'catalog',
                'name' => 'Catalog',
                'version' => '0.1.0',
                'enabled' => true,
                'owner' => 'modernization',
                'feature_ids' => ['SF-003'],
                'dependencies' => ['missing-foundation'],
                'providers' => [],
                'routes' => [],
                'commands' => [],
                'events' => [],
                'listeners' => [],
                'permissions' => [],
                'config' => [],
                'views' => [],
                'jobs' => [],
                'policies' => [],
            ],
            [
                'id' => 'catalog',
                'name' => 'Catalog Duplicate',
                'version' => '0.1.0',
                'enabled' => true,
                'owner' => 'modernization',
                'feature_ids' => ['SF-003'],
                'dependencies' => [],
                'providers' => [],
                'routes' => [],
                'commands' => [],
                'events' => [],
                'listeners' => [],
                'permissions' => [],
                'config' => [],
                'views' => [],
                'jobs' => [],
                'policies' => [],
            ],
        ]);

        $this->assertFalse($registry->healthy());
        $this->assertContains('Duplicate module manifest id [catalog].', $registry->errors());
        $this->assertContains('Module [catalog] depends on missing module [missing-foundation].', $registry->errors());
    }

    public function test_module_registry_reports_dependency_cycle_diagnostics(): void
    {
        $registry = ModuleRegistry::fromConfig([
            [
                'id' => 'catalog',
                'name' => 'Catalog',
                'version' => '0.1.0',
                'enabled' => true,
                'owner' => 'modernization',
                'feature_ids' => ['SF-003'],
                'dependencies' => ['checkout'],
                'providers' => [],
                'routes' => [],
                'commands' => [],
                'events' => [],
                'listeners' => [],
                'permissions' => [],
                'config' => [],
                'views' => [],
                'jobs' => [],
                'policies' => [],
            ],
            [
                'id' => 'checkout',
                'name' => 'Checkout',
                'version' => '0.1.0',
                'enabled' => true,
                'owner' => 'modernization',
                'feature_ids' => ['CB-003'],
                'dependencies' => ['catalog'],
                'providers' => [],
                'routes' => [],
                'commands' => [],
                'events' => [],
                'listeners' => [],
                'permissions' => [],
                'config' => [],
                'views' => [],
                'jobs' => [],
                'policies' => [],
            ],
        ]);

        $this->assertFalse($registry->healthy());
        $this->assertContains('Module dependency cycle detected: catalog -> checkout -> catalog.', $registry->errors());
    }

    public function test_module_registry_policy_allows_local_diagnostics(): void
    {
        $policy = $this->app->make(ModuleRegistryPolicy::class);

        $this->assertTrue($policy->viewDiagnostics());
    }
}
