<?php

namespace App\Http\Controllers\Modernization;

use App\Http\Controllers\Controller;
use App\Modernization\Routing\RouteOwner;
use App\Modernization\Routing\RouteOwnership;
use App\Modernization\Routing\RouteOwnershipDecision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LegacyFallbackController extends Controller
{
    public function __invoke(Request $request, RouteOwnership $routeOwnership): JsonResponse|RedirectResponse
    {
        $decision = $routeOwnership->resolve($request);

        Log::withContext([
            'route_owner' => $decision->owner->value,
            'route_feature' => $decision->feature,
            'route_feature_flag' => $decision->featureFlag,
            'route_rollback' => $decision->rollback,
            'route_path' => $decision->path,
        ]);
        Log::info('Route fallback decision recorded');

        if ($decision->owner === RouteOwner::Laravel) {
            return response()
                ->json($decision->toArray() + [
                    'status' => 'laravel_route_missing',
                    'message' => 'This path is marked Laravel-owned and must be registered explicitly before it can serve traffic.',
                ], 404)
                ->withHeaders($this->headers($decision));
        }

        if (! $decision->fallbackEnabled) {
            return response()
                ->json($decision->toArray() + [
                    'status' => 'legacy_fallback_disabled',
                    'message' => 'Legacy fallback is observable but disabled until the auth and session boundary is approved.',
                ], 503)
                ->withHeaders($this->headers($decision));
        }

        $response = redirect()->away(
            $decision->legacyUrl((string) config('route_ownership.legacy_base_url'), $request),
            307,
        );

        foreach ($this->headers($decision) as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }

    /**
     * @return array<string, string>
     */
    private function headers(RouteOwnershipDecision $decision): array
    {
        $headers = [
            'X-Route-Owner' => $decision->owner->value,
            'X-Route-Feature' => $decision->feature,
            'X-Route-Rollback' => $decision->rollback,
            'X-Route-Fallback-Enabled' => $decision->fallbackEnabled ? 'true' : 'false',
            'X-Admin-Frontname' => $decision->adminFrontname ? 'true' : 'false',
        ];

        if ($decision->storeCode !== null) {
            $headers['X-Store-Code'] = $decision->storeCode;
        }

        return $headers;
    }
}
