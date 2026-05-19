<?php

namespace App\Providers;

use App\Modernization\Eav\Contracts\EavAttributeValueReaderContract;
use App\Modernization\Eav\Repositories\EavAttributeValueReader;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class EavServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(EavAttributeValueReader::class, function (Application $app): EavAttributeValueReader {
            return new EavAttributeValueReader($app->make('db')->connection());
        });

        $this->app->singleton(
            EavAttributeValueReaderContract::class,
            fn (Application $app): EavAttributeValueReader => $app->make(EavAttributeValueReader::class),
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
