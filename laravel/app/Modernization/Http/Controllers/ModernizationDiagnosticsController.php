<?php

namespace App\Modernization\Http\Controllers;

use App\Modernization\Modules\ModuleRegistry;
use App\Modernization\Modules\ModuleRegistryHealth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final readonly class ModernizationDiagnosticsController
{
    public function __construct(
        private ModuleRegistry $registry,
        private ModuleRegistryHealth $health,
    ) {}

    public function modules(): JsonResponse
    {
        Gate::authorize('viewModuleRegistryDiagnostics');

        return response()->json([
            'health' => $this->health->report(),
            'registry' => $this->registry->toArray(),
        ], $this->registry->healthy() ? 200 : 500);
    }
}
