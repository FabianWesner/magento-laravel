<?php

namespace App\Providers;

use App\Modernization\Modules\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

class ModernizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class, function (): ModuleRegistry {
            $modules = config('modernization.modules', []);

            return ModuleRegistry::fromConfig(is_array($modules) ? $modules : []);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
