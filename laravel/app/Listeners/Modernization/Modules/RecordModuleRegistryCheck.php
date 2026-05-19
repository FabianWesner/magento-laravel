<?php

namespace App\Listeners\Modernization\Modules;

use App\Events\Modernization\Modules\ModuleRegistryChecked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class RecordModuleRegistryCheck implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ModuleRegistryChecked $event): void
    {
        Log::info('Modernization module registry checked', $event->health);
    }
}
