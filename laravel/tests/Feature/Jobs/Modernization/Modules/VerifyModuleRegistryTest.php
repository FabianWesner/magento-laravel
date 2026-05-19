<?php

namespace Tests\Feature\Jobs\Modernization\Modules;

use App\Events\Modernization\Modules\ModuleRegistryChecked;
use App\Jobs\Modernization\Modules\VerifyModuleRegistry;
use App\Modernization\Modules\ModuleRegistryHealth;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class VerifyModuleRegistryTest extends TestCase
{
    public function test_job_dispatches_module_registry_health_event(): void
    {
        Event::fake();

        (new VerifyModuleRegistry)->handle($this->app->make(ModuleRegistryHealth::class));

        Event::assertDispatched(
            ModuleRegistryChecked::class,
            fn (ModuleRegistryChecked $event): bool => ($event->health['status'] ?? null) === 'ok',
        );
    }
}
