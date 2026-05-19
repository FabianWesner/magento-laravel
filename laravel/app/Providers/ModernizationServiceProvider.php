<?php

namespace App\Providers;

use App\Events\Modernization\Modules\ModuleRegistryChecked;
use App\Listeners\Modernization\Modules\RecordModuleRegistryCheck;
use App\Modernization\Api\LegacyApiContract;
use App\Modernization\Config\ConfigRepository;
use App\Modernization\Config\Contracts\ConfigRepositoryContract;
use App\Modernization\Modules\Contracts\ModuleRegistryContract;
use App\Modernization\Modules\ModuleRegistry;
use App\Policies\LegacyApiContractPolicy;
use App\Policies\Modernization\Modules\ModuleRegistryPolicy;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ModernizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ConfigRepositoryContract::class, function (Application $app): ConfigRepository {
            return new ConfigRepository($app->make('db')->connection());
        });

        $this->app->singleton(ModuleRegistry::class, function (): ModuleRegistry {
            $modules = config('modernization.modules', []);

            return ModuleRegistry::fromConfig(is_array($modules) ? $modules : []);
        });

        $this->app->singleton(
            ModuleRegistryContract::class,
            fn (): ModuleRegistry => $this->app->make(ModuleRegistry::class),
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::policy(LegacyApiContract::class, LegacyApiContractPolicy::class);
        Gate::define('viewModuleRegistryDiagnostics', [ModuleRegistryPolicy::class, 'viewDiagnostics']);
        Event::listen(ModuleRegistryChecked::class, RecordModuleRegistryCheck::class);
    }
}
