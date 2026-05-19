<?php

use App\Http\Controllers\Modernization\AuthBoundaryController;
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

Route::get('/_modernization/livewire-parity', function () {
    return view('modernization.livewire-parity');
})->name('modernization.livewire-parity');

Route::get('/_modernization/assets/livewire-parity.css', function () {
    return response()->file(public_path('modernization/livewire-parity.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.livewire-parity');

Route::get('/_modernization/admin/reports', function () {
    return view('modernization.admin-reports');
})->name('modernization.admin.reports');

Route::get('/_modernization/assets/admin-reports.css', function () {
    return response()->file(public_path('modernization/admin-reports.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.admin-reports');

Route::middleware('guest')->prefix('_modernization/auth')->group(function (): void {
    Route::get('/customer/login', [AuthBoundaryController::class, 'password'])->name('modernization.auth.customer.login');
    Route::get('/admin/login', [AuthBoundaryController::class, 'password'])->name('login');
    Route::get('/forgot-password', [AuthBoundaryController::class, 'password'])->name('password.request');
    Route::post('/forgot-password', [AuthBoundaryController::class, 'password'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthBoundaryController::class, 'password'])->name('password.reset');
});

Route::get('/_modernization/auth/customer/session', [AuthBoundaryController::class, 'customer'])
    ->middleware('auth:customer')
    ->name('modernization.auth.customer.session');

Route::get('/_modernization/auth/admin/session', [AuthBoundaryController::class, 'admin'])
    ->middleware(['auth:admin', 'can:admin.access'])
    ->name('modernization.auth.admin.session');

Route::post('/_modernization/auth/csrf-form-key', [AuthBoundaryController::class, 'formKey'])
    ->name('modernization.auth.csrf-form-key');

Route::post('/_modernization/auth/logout-boundary', [AuthBoundaryController::class, 'logoutBoundary'])
    ->middleware('auth:customer')
    ->name('modernization.auth.logout-boundary');

Route::match(['POST', 'PUT', 'PATCH', 'DELETE'], '{legacyFallbackPath}', LegacyFallbackController::class)
    ->where('legacyFallbackPath', '.*')
    ->name('modernization.legacy-stateful-fallback');

Route::fallback(LegacyFallbackController::class)
    ->name('modernization.legacy-fallback');
