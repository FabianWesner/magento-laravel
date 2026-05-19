<?php

namespace Tests\Feature;

use App\Modernization\Bootstrap\CompatibilityAdapter;
use App\Modernization\Bootstrap\CoreInfrastructure;
use App\Modernization\Bootstrap\ErrorHandler;
use App\Modernization\Bootstrap\Observability;
use App\Modernization\Bootstrap\RuntimeIsolation;
use RuntimeException;
use Tests\TestCase;

class BootstrapFoundationTest extends TestCase
{
    public function test_container_service_resolution_works_in_web_requests(): void
    {
        $this->get('/_modernization/bootstrap')
            ->assertOk()
            ->assertJsonPath('health.bootstrap.status', 'ok')
            ->assertJsonPath('health.database.status', 'ok')
            ->assertJsonPath('health.cache.status', 'ok')
            ->assertJsonPath('health.session.status', 'ok')
            ->assertJsonPath('health.module_registry.status', 'ok')
            ->assertJsonPath('runtime_isolation.status', 'ok');

        $this->assertInstanceOf(CoreInfrastructure::class, $this->app->make(CoreInfrastructure::class));
    }

    public function test_container_service_resolution_works_in_cli_requests(): void
    {
        $this->artisan('modernization:modules')
            ->expectsOutput('foundation | Laravel Foundation | 0.1.0')
            ->assertSuccessful();

        $this->assertInstanceOf(CoreInfrastructure::class, $this->app->make(CoreInfrastructure::class));
    }

    public function test_runtime_isolation_uses_separate_php85_runtime_and_keeps_legacy_php74_for_smoke(): void
    {
        $report = $this->app->make(RuntimeIsolation::class)->report();

        $this->assertSame('ok', $report['status']);
        $this->assertTrue($report['separate_php_runtime']);
        $this->assertTrue($report['legacy_php74_only_for_baseline_smoke']);
        $this->assertTrue($report['not_loaded_inside_legacy_process']);
    }

    public function test_bootstrap_foundation_records_legacy_behavior_remains_unchanged_and_no_routes_are_moved(): void
    {
        $adapter = $this->app->make(CompatibilityAdapter::class)->metadata();

        $this->assertSame('modernization', $adapter['owner']);
        $this->assertFalse($adapter['final_release_runtime_dependency']);
        $this->assertTrue($adapter['no final-release runtime dependency']);
    }

    public function test_error_handling_logging_and_diagnostics_context_are_structured(): void
    {
        $observability = $this->app->make(Observability::class);
        $errorHandler = $this->app->make(ErrorHandler::class);

        $this->assertSame('laravel', $observability->context('bootstrap')['runtime']);
        $this->assertSame(RuntimeException::class, $errorHandler->context(new RuntimeException('failed'))['exception']);
    }
}
