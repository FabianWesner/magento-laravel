<?php

namespace Tests\Feature\Listeners\Modernization\Modules;

use App\Events\Modernization\Modules\ModuleRegistryChecked;
use App\Listeners\Modernization\Modules\RecordModuleRegistryCheck;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RecordModuleRegistryCheckTest extends TestCase
{
    public function test_listener_records_module_registry_health_context(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Modernization module registry checked', ['status' => 'ok']);

        (new RecordModuleRegistryCheck)->handle(new ModuleRegistryChecked(['status' => 'ok']));
    }
}
