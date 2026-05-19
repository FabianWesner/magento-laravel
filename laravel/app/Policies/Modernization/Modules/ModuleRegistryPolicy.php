<?php

namespace App\Policies\Modernization\Modules;

use App\Models\User;
use Illuminate\Contracts\Foundation\Application;

final readonly class ModuleRegistryPolicy
{
    public function __construct(private Application $app) {}

    public function viewDiagnostics(?User $user = null): bool
    {
        return $this->app->environment(['local', 'testing']);
    }
}
