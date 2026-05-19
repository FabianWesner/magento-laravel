<?php

use App\Events\Modernization\Modules\ModuleRegistryChecked;
use App\Jobs\Modernization\Modules\VerifyModuleRegistry;
use App\Listeners\Modernization\Modules\RecordModuleRegistryCheck;
use App\Policies\Modernization\Modules\ModuleRegistryPolicy;
use App\Providers\ModernizationServiceProvider;

return [
    /*
    |--------------------------------------------------------------------------
    | PHP-first modernization modules
    |--------------------------------------------------------------------------
    |
    | Migrated Laravel behavior is registered through typed PHP manifests,
    | providers, policies, events, jobs, routes, and config. Magento XML may be
    | inventoried separately, but it is not a runtime extension mechanism here.
    |
    */
    'modules' => [
        [
            'id' => 'foundation',
            'name' => 'Laravel Foundation',
            'version' => '0.1.0',
            'enabled' => true,
            'owner' => 'modernization',
            'feature_ids' => ['ARCH'],
            'dependencies' => [],
            'providers' => [
                ModernizationServiceProvider::class,
            ],
            'routes' => [
                '/_modernization/modules',
            ],
            'commands' => [
                'modernization:modules',
            ],
            'events' => [
                ModuleRegistryChecked::class,
            ],
            'listeners' => [
                RecordModuleRegistryCheck::class,
            ],
            'permissions' => [
                'modernization.modules.view',
            ],
            'config' => [
                'modernization.modules',
            ],
            'views' => [],
            'jobs' => [
                VerifyModuleRegistry::class,
            ],
            'policies' => [
                ModuleRegistryPolicy::class,
            ],
        ],
    ],
];
