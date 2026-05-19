<?php

namespace App\Http\Controllers\Modernization;

use App\Http\Controllers\Controller;
use App\Modernization\Domain\DomainQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DomainDiagnosticsController extends Controller
{
    public function show(Request $request, DomainQueryService $queryService): JsonResponse
    {
        Gate::authorize('viewDomainDiagnostics');

        return response()->json($queryService->snapshot(
            $request->string('domain', 'catalog')->toString(),
            [
                'store_id' => $request->query('store_id'),
                'store_view' => $request->query('store_view'),
            ],
        ));
    }
}
