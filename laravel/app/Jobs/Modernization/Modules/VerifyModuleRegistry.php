<?php

namespace App\Jobs\Modernization\Modules;

use App\Events\Modernization\Modules\ModuleRegistryChecked;
use App\Modernization\Modules\ModuleRegistryHealth;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class VerifyModuleRegistry implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(ModuleRegistryHealth $health): void
    {
        ModuleRegistryChecked::dispatch($health->report());
    }
}
