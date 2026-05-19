<?php

namespace App\Providers;

use App\Modernization\Bootstrap\CoreInfrastructure;
use App\Modernization\Bootstrap\Health\BootstrapHealth;
use App\Modernization\Bootstrap\Health\CacheHealth;
use App\Modernization\Bootstrap\Health\DatabaseHealth;
use App\Modernization\Bootstrap\Health\SessionHealth;
use App\Modernization\Modules\ModuleRegistryHealth;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class InfrastructureServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CoreInfrastructure::class, function (Application $app): CoreInfrastructure {
            return new CoreInfrastructure(
                config: $app->make('config'),
                database: $app->make('db'),
                cache: $app->make('cache'),
                session: $app->make('session'),
                events: $app->make('events'),
                filesystem: $app->make('filesystem'),
                url: $app->make('url'),
                auth: $app->make('auth'),
                translator: $app->make('translator'),
                healthChecks: [
                    $app->make(BootstrapHealth::class),
                    $app->make(DatabaseHealth::class),
                    $app->make(CacheHealth::class),
                    $app->make(SessionHealth::class),
                    $app->make(ModuleRegistryHealth::class),
                ],
            );
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
