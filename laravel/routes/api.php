<?php

use App\Http\Controllers\Api\LegacyApiContractController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$apiVersion = 'v1';

Route::prefix($apiVersion)
    ->middleware(['throttle:api'])
    ->as('api.v1.')
    ->group(function () use ($apiVersion): void {
        Route::apiResource('contracts', LegacyApiContractController::class)
            ->parameters(['contracts' => 'contract']);

        Route::fallback(function (Request $request) use ($apiVersion) {
            return response()->json([
                'error' => [
                    'code' => 'legacy_api_route_not_migrated',
                    'message' => 'Legacy API fallback is tracked but this API route is not migrated yet.',
                    'status' => 404,
                ],
                'version' => $apiVersion,
                'path' => $request->path(),
            ], 404);
        })->name('legacy-fallback');
    });
