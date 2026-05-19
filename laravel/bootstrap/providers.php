<?php

use App\Providers\AppServiceProvider;
use App\Providers\BootstrapServiceProvider;
use App\Providers\EavServiceProvider;
use App\Providers\InfrastructureServiceProvider;
use App\Providers\ModernizationServiceProvider;

return [
    AppServiceProvider::class,
    BootstrapServiceProvider::class,
    EavServiceProvider::class,
    InfrastructureServiceProvider::class,
    ModernizationServiceProvider::class,
];
