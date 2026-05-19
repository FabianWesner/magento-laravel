<?php

use App\Events\Modernization\Modules\ModuleRegistryChecked;
use App\Http\Controllers\Api\LegacyApiContractController;
use App\Http\Requests\Api\LegacyApiContractIndexRequest;
use App\Http\Resources\LegacyApiContractResource;
use App\Jobs\Modernization\Cron\CaptureCronParitySnapshot;
use App\Jobs\Modernization\Modules\VerifyModuleRegistry;
use App\Listeners\Modernization\Modules\RecordModuleRegistryCheck;
use App\Modernization\Config\ScopedConfig;
use App\Policies\LegacyApiContractPolicy;
use App\Policies\Modernization\Cron\CronPolicy;
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
                '/api/v1/contracts',
                '/_modernization/modules',
                '/_modernization/admin/cron-jobs',
            ],
            'commands' => [
                'modernization:modules',
                'modernization:cron-status',
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
                'api_contracts.contracts',
                'cron_jobs.jobs',
                'modernization.modules',
                'scoped_config.implementation',
            ],
            'views' => [
                'modernization.cron-jobs',
                'livewire.cron-jobs-workbench',
            ],
            'jobs' => [
                CaptureCronParitySnapshot::class,
                VerifyModuleRegistry::class,
            ],
            'policies' => [
                LegacyApiContractPolicy::class,
                CronPolicy::class,
                ModuleRegistryPolicy::class,
            ],
            'api' => [
                LegacyApiContractController::class,
                LegacyApiContractIndexRequest::class,
                LegacyApiContractResource::class,
            ],
            'typed_config' => [
                ScopedConfig::class,
            ],
        ],
    ],
];
