<?php

namespace App\Providers;

use App\Modernization\Bootstrap\AdapterExpiry;
use App\Modernization\Bootstrap\CompatibilityAdapter;
use App\Modernization\Bootstrap\ErrorHandler;
use App\Modernization\Bootstrap\Observability;
use App\Modernization\Bootstrap\RuntimeIsolation;
use Illuminate\Support\ServiceProvider;

class BootstrapServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(RuntimeIsolation::class, function (): RuntimeIsolation {
            return new RuntimeIsolation(
                laravelBasePath: base_path(),
                legacyCorePath: dirname(base_path()).'/core/magento-1.9.4.5',
                phpVersionId: PHP_VERSION_ID,
            );
        });

        $this->app->singleton(AdapterExpiry::class, fn (): AdapterExpiry => new AdapterExpiry('Phase 5 feature migration'));

        $this->app->singleton(CompatibilityAdapter::class, function (): CompatibilityAdapter {
            return new CompatibilityAdapter(
                owner: 'modernization',
                expiry: $this->app->make(AdapterExpiry::class),
                finalReleaseRuntimeDependency: false,
            );
        });

        $this->app->singleton(Observability::class);
        $this->app->singleton(ErrorHandler::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
