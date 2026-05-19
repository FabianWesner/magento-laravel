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

Route::get('/_modernization/admin/import-export', function () {
    return view('modernization.import-export');
})->name('modernization.admin.import-export');

Route::get('/_modernization/assets/import-export.css', function () {
    return response()->file(public_path('modernization/import-export.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.import-export');

Route::get('/_modernization/admin/system-config', function () {
    return view('modernization.system-config');
})->name('modernization.admin.system-config');

Route::get('/_modernization/assets/system-config.css', function () {
    return response()->file(public_path('modernization/system-config.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.system-config');

Route::get('/_modernization/admin/tax-currency', function () {
    return view('modernization.tax-currency');
})->name('modernization.admin.tax-currency');

Route::get('/_modernization/assets/tax-currency.css', function () {
    return response()->file(public_path('modernization/tax-currency.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.tax-currency');

Route::get('/_modernization/admin/cron-jobs', function () {
    return view('modernization.cron-jobs');
})->name('modernization.admin.cron-jobs');

Route::get('/_modernization/assets/cron-jobs.css', function () {
    return response()->file(public_path('modernization/cron-jobs.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.cron-jobs');

Route::get('/_modernization/admin/integration-api', function () {
    return view('modernization.integration-api');
})->name('modernization.admin.integration-api');

Route::get('/_modernization/assets/integration-api.css', function () {
    return response()->file(public_path('modernization/integration-api.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.integration-api');

Route::get('/_modernization/admin/admin-permissions', function () {
    return view('modernization.admin-permissions');
})->name('modernization.admin.admin-permissions');

Route::get('/_modernization/assets/admin-permissions.css', function () {
    return response()->file(public_path('modernization/admin-permissions.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.admin-permissions');

Route::get('/_modernization/admin/catalog-management', function () {
    return view('modernization.admin-catalog');
})->name('modernization.admin.catalog-management');

Route::get('/_modernization/assets/admin-catalog.css', function () {
    return response()->file(public_path('modernization/admin-catalog.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.admin-catalog');

Route::get('/_modernization/admin/customer-management', function () {
    return view('modernization.admin-customer');
})->name('modernization.admin.customer-management');

Route::get('/_modernization/assets/admin-customer.css', function () {
    return response()->file(public_path('modernization/admin-customer.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.admin-customer');

Route::get('/_modernization/admin/cms-design', function () {
    return view('modernization.admin-cms-design');
})->name('modernization.admin.cms-design');

Route::get('/_modernization/assets/admin-cms-design.css', function () {
    return response()->file(public_path('modernization/admin-cms-design.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.admin-cms-design');

Route::get('/_modernization/admin/newsletter-polls', function () {
    return view('modernization.admin-newsletter-polls');
})->name('modernization.admin.newsletter-polls');

Route::get('/_modernization/assets/admin-newsletter-polls.css', function () {
    return response()->file(public_path('modernization/admin-newsletter-polls.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.admin-newsletter-polls');

Route::get('/_modernization/admin/store-operations', function () {
    return view('modernization.admin-store-operations');
})->name('modernization.admin.store-operations');

Route::get('/_modernization/assets/admin-store-operations.css', function () {
    return response()->file(public_path('modernization/admin-store-operations.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.admin-store-operations');

Route::get('/_modernization/admin/sales-fulfillment', function () {
    return view('modernization.admin-sales-fulfillment');
})->name('modernization.admin.sales-fulfillment');

Route::get('/_modernization/assets/admin-sales-fulfillment.css', function () {
    return response()->file(public_path('modernization/admin-sales-fulfillment.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.admin-sales-fulfillment');

Route::get('/_modernization/admin/promotions', function () {
    return view('modernization.admin-promotions');
})->name('modernization.admin.promotions');

Route::get('/_modernization/assets/admin-promotions.css', function () {
    return response()->file(public_path('modernization/admin-promotions.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.admin-promotions');

Route::get('/_modernization/storefront/cart', function () {
    return view('modernization.storefront-cart');
})->name('modernization.storefront.cart');

Route::get('/_modernization/assets/storefront-cart.css', function () {
    return response()->file(public_path('modernization/storefront-cart.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.storefront-cart');

Route::get('/_modernization/storefront/checkout', function () {
    return view('modernization.storefront-checkout');
})->name('modernization.storefront.checkout');

Route::get('/_modernization/assets/storefront-checkout.css', function () {
    return response()->file(public_path('modernization/storefront-checkout.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]);
})->name('modernization.assets.storefront-checkout');

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
