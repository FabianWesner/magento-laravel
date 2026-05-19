<?php

use App\Modernization\Http\Controllers\ModernizationDiagnosticsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/_modernization/modules', [ModernizationDiagnosticsController::class, 'modules'])
    ->name('modernization.modules');
