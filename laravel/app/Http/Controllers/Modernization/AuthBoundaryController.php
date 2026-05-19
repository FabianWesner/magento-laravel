<?php

namespace App\Http\Controllers\Modernization;

use App\Http\Controllers\Controller;
use App\Modernization\Auth\AdminSessionBridge;
use App\Modernization\Auth\CustomerSessionBridge;
use App\Modernization\Auth\FormKeyCompatibility;
use App\Modernization\Auth\SessionCookieBoundary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthBoundaryController extends Controller
{
    public function customer(Request $request, CustomerSessionBridge $bridge): JsonResponse
    {
        return response()->json([
            'feature_id' => 'SF-010',
            'session' => $bridge->boundary($request->session()->all()),
        ]);
    }

    public function admin(Request $request, AdminSessionBridge $bridge): JsonResponse
    {
        return response()->json([
            'feature_id' => 'AD-001',
            'session' => $bridge->boundary($request->session()->all(), (string) $request->user('admin')?->getAttribute('role')),
        ]);
    }

    public function password(): JsonResponse
    {
        return response()->json([
            'feature_id' => 'AD-012',
            'brokers' => ['customers', 'admins'],
        ]);
    }

    public function formKey(Request $request, FormKeyCompatibility $formKeyCompatibility): JsonResponse
    {
        return response()->json([
            'feature_id' => 'CB-013',
            'form_key' => $formKeyCompatibility->inspect(
                $request->string('form_key')->toString() ?: null,
                $request->session()->get('_form_key'),
            ),
        ]);
    }

    public function logoutBoundary(SessionCookieBoundary $boundary): JsonResponse
    {
        return response()->json([
            'feature_id' => 'SF-010',
            'cookie' => $boundary->cookie(),
            'logout' => $boundary->logoutInvalidation(),
        ]);
    }
}
