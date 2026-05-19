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

Route::get('/favicon.ico', function () {
    return response('', 204);
})->name('favicon');

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

Route::get('/_modernization/admin/cache-index', function () {
    return view('modernization.cache-index');
})->name('modernization.admin.cache-index');

Route::get('/_modernization/assets/cache-index.css', function () {
    return response()->file(public_path('modernization/cache-index.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.cache-index');

Route::get('/_modernization/storefront/catalog', function () {
    return view('modernization.storefront-catalog');
})->name('modernization.storefront.catalog');

Route::get('/_modernization/assets/storefront-catalog.css', function () {
    return response()->file(public_path('modernization/storefront-catalog.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.storefront-catalog');

Route::get('/_modernization/customer/account', function () {
    return view('modernization.customer-account');
})->name('modernization.customer.account');

Route::get('/_modernization/assets/customer-account.css', function () {
    return response()->file(public_path('modernization/customer-account.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.customer-account');

Route::get('/_modernization/storefront/product-detail', function () {
    return view('modernization.product-detail');
})->name('modernization.storefront.product-detail');

Route::get('/_modernization/assets/product-detail.css', function () {
    return response()->file(public_path('modernization/product-detail.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.product-detail');

Route::get('/_modernization/storefront/search', function () {
    return view('modernization.search');
})->name('modernization.storefront.search');

Route::get('/_modernization/assets/search.css', function () {
    return response()->file(public_path('modernization/search.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.search');

Route::get('/_modernization/storefront/cms-seo', function () {
    return view('modernization.cms-seo');
})->name('modernization.storefront.cms-seo');

Route::get('/_modernization/assets/cms-seo.css', function () {
    return response()->file(public_path('modernization/cms-seo.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.cms-seo');

Route::get('/_modernization/storefront/communications', function () {
    return view('modernization.communications');
})->name('modernization.storefront.communications');

Route::get('/_modernization/assets/communications.css', function () {
    return response()->file(public_path('modernization/communications.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.communications');

Route::get('/_modernization/customer/commerce', function () {
    return view('modernization.customer-commerce');
})->name('modernization.customer.commerce');

Route::get('/_modernization/assets/customer-commerce.css', function () {
    return response()->file(public_path('modernization/customer-commerce.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.customer-commerce');

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
