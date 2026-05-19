<?php

use App\Http\Controllers\Modernization\LegacyFallbackController;
use App\Modernization\Bootstrap\CoreInfrastructure;
use App\Modernization\Bootstrap\RuntimeIsolation;
use App\Modernization\Http\Controllers\ModernizationDiagnosticsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/_modernization/modules', [ModernizationDiagnosticsController::class, 'modules'])
    ->name('modernization.modules');

Route::get('/_modernization/bootstrap', function (CoreInfrastructure $infrastructure, RuntimeIsolation $runtimeIsolation) {
    return response()->json([
        'infrastructure' => $infrastructure->summary(),
        'health' => $infrastructure->health(),
        'runtime_isolation' => $runtimeIsolation->report(),
    ]);
})->name('modernization.bootstrap');

Route::match(['POST', 'PUT', 'PATCH', 'DELETE'], '{legacyFallbackPath}', LegacyFallbackController::class)
    ->where('legacyFallbackPath', '.*')
    ->name('modernization.legacy-stateful-fallback');

Route::fallback(LegacyFallbackController::class)
    ->name('modernization.legacy-fallback');
