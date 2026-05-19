<?php

use App\Events\Modernization\Modules\ModuleRegistryChecked;
use App\Http\Controllers\Api\LegacyApiContractController;
use App\Http\Requests\Api\LegacyApiContractIndexRequest;
use App\Http\Resources\LegacyApiContractResource;
use App\Jobs\Modernization\Cron\CaptureCronParitySnapshot;
use App\Jobs\Modernization\Modules\VerifyModuleRegistry;
use App\Listeners\Modernization\Modules\RecordModuleRegistryCheck;
use App\Modernization\Auth\AdminPermissionCatalog;
use App\Modernization\Auth\PermissionManifest;
use App\Modernization\Config\ScopedConfig;
use App\Policies\LegacyApiContractPolicy;
use App\Policies\Modernization\Auth\AdminPermissionPolicy;
use App\Policies\Modernization\Cron\CronPolicy;
use App\Policies\Modernization\Integrations\IntegrationPolicy;
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
                '/_modernization/admin/integration-api',
                '/_modernization/admin/admin-permissions',
                '/_modernization/admin/catalog-management',
                '/_modernization/admin/customer-management',
                '/_modernization/admin/cms-design',
                '/_modernization/admin/newsletter-polls',
                '/_modernization/admin/store-operations',
                '/_modernization/admin/sales-fulfillment',
                '/_modernization/admin/promotions',
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
                'integrations.adapters',
                'modernization.modules',
                PermissionManifest::class,
                'scoped_config.implementation',
            ],
            'views' => [
                'modernization.cron-jobs',
                'livewire.cron-jobs-workbench',
                'modernization.integration-api',
                'livewire.integration-api-workbench',
                'modernization.admin-permissions',
                'livewire.admin-permissions-workbench',
                'modernization.admin-catalog',
                'livewire.admin-catalog-workbench',
                'modernization.admin-customer',
                'livewire.admin-customer-workbench',
                'modernization.admin-cms-design',
                'livewire.admin-cms-design-workbench',
                'modernization.admin-newsletter-polls',
                'livewire.admin-newsletter-polls-workbench',
                'modernization.admin-store-operations',
                'livewire.admin-store-operations-workbench',
                'modernization.admin-sales-fulfillment',
                'livewire.admin-sales-fulfillment-workbench',
                'modernization.admin-promotions',
                'livewire.admin-promotions-workbench',
            ],
            'jobs' => [
                CaptureCronParitySnapshot::class,
                VerifyModuleRegistry::class,
            ],
            'policies' => [
                LegacyApiContractPolicy::class,
                AdminPermissionPolicy::class,
                CronPolicy::class,
                IntegrationPolicy::class,
                ModuleRegistryPolicy::class,
            ],
            'api' => [
                LegacyApiContractController::class,
                LegacyApiContractIndexRequest::class,
                LegacyApiContractResource::class,
            ],
            'typed_config' => [
                AdminPermissionCatalog::class,
                ScopedConfig::class,
            ],
        ],
    ],
];
