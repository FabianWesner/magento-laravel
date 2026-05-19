<?php

namespace App\Http\Controllers\Modernization;

use App\Http\Controllers\Controller;
use App\Modernization\Reports\ReportCatalog;
use App\Modernization\Reports\ReportQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReportDiagnosticsController extends Controller
{
    public const ADMIN_SURFACE = 'ReportDiagnosticsComponent';

    public function index(Request $request, ReportCatalog $catalog, ReportQuery $query): JsonResponse
    {
        Gate::authorize('viewReports');

        $definition = $catalog->get($request->string('report', 'sales')->toString());
        $filters = [
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'store_id' => $request->query('store_id'),
            'currency' => $request->query('currency'),
        ];

        return response()->json($query->run($definition, array_filter($filters))->toArray());
    }

    public function export(Request $request, ReportCatalog $catalog, ReportQuery $query): JsonResponse
    {
        Gate::authorize('viewReports');

        $definition = $catalog->get($request->string('report', 'sales')->toString());
        $result = $query->run($definition, [
            'currency' => $request->query('currency'),
        ]);

        return response()->json([
            'report' => $definition->key,
            'export' => 'csv',
            'csv' => $query->exportCsv($result),
            'empty_state' => $result->count === 0,
        ]);
    }
}
