<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LegacyApiContractIndexRequest;
use App\Http\Resources\LegacyApiContractResource;
use App\Modernization\Api\LegacyApiContractRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class LegacyApiContractController extends Controller
{
    public function __construct(private LegacyApiContractRepository $contracts) {}

    public function index(LegacyApiContractIndexRequest $request): AnonymousResourceCollection
    {
        return LegacyApiContractResource::collection($this->contracts->all($request->validated()))
            ->additional([
                'meta' => [
                    'version' => (string) config('api_contracts.version', 'v1'),
                    'legacy_api_compatibility' => true,
                ],
            ]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->methodNotAllowed('api_contracts_are_read_only');
    }

    public function show(string $contract): LegacyApiContractResource|JsonResponse
    {
        $apiContract = $this->contracts->find($contract);
        if ($apiContract === null) {
            return $this->notFound($contract);
        }

        return new LegacyApiContractResource($apiContract);
    }

    public function update(Request $request, string $contract): JsonResponse
    {
        return $this->methodNotAllowed('api_contracts_are_read_only');
    }

    public function destroy(string $contract): JsonResponse
    {
        return $this->methodNotAllowed('api_contracts_are_read_only');
    }

    private function notFound(string $featureId): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => 'api_contract_not_found',
                'message' => "API contract [{$featureId}] is not in the legacy compatibility inventory.",
                'status' => 404,
            ],
        ], 404);
    }

    private function methodNotAllowed(string $code): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => 'API contract inventory is read-only until endpoint parity is approved.',
                'status' => 405,
            ],
        ], 405);
    }
}
