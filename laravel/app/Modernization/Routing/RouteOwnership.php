<?php

namespace App\Modernization\Routing;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class RouteOwnership
{
    public function __construct(private Repository $config) {}

    public function resolve(Request $request): RouteOwnershipDecision
    {
        $path = trim($request->path(), '/');
        $path = $path === '' ? '/' : $path;
        $route = $this->matchingRoute($path, $request->method());

        return new RouteOwnershipDecision(
            owner: RouteOwner::fromConfig((string) ($route['owner'] ?? $this->config->get('route_ownership.default_owner', 'legacy'))),
            feature: (string) ($route['feature'] ?? 'LEGACY_FALLBACK'),
            featureFlag: (string) ($route['feature_flag'] ?? 'route.legacy.fallback'),
            rollback: (string) ($route['rollback'] ?? $this->config->get('route_ownership.fallback.rollback', 'legacy')),
            routePattern: (string) ($route['pattern'] ?? '*'),
            path: $path,
            method: $request->method(),
            fallbackEnabled: (bool) $this->config->get('route_ownership.fallback.enabled', false),
            storeCode: $this->storeCode($path),
            adminFrontname: $this->isAdminFrontname($path),
            formKeyPresent: $this->hasFormKey($request),
            customerSessionPresent: $this->sessionHas($request, 'customer_id'),
            adminSessionPresent: $this->sessionHas($request, 'admin_user_id'),
            sessionBoundary: $this->sessionBoundary(),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function matchingRoute(string $path, string $method): ?array
    {
        $routes = $this->config->get('route_ownership.routes', []);
        if (! is_array($routes)) {
            return null;
        }

        foreach ($routes as $route) {
            if (! is_array($route)) {
                continue;
            }

            $pattern = (string) ($route['pattern'] ?? '');
            if ($pattern === '' || ! Str::is($pattern, $path)) {
                continue;
            }

            if (! $this->supportsMethod($route, $method)) {
                continue;
            }

            return $route;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $route
     */
    private function supportsMethod(array $route, string $method): bool
    {
        $methods = $route['methods'] ?? [];
        if (! is_array($methods) || $methods === []) {
            return true;
        }

        return in_array(strtoupper($method), array_map('strtoupper', $methods), true);
    }

    private function storeCode(string $path): ?string
    {
        $firstSegment = Str::before($path, '/');
        $storeCodes = $this->config->get('route_ownership.store_codes', []);

        if (! is_array($storeCodes)) {
            return null;
        }

        return in_array($firstSegment, $storeCodes, true) ? $firstSegment : null;
    }

    private function isAdminFrontname(string $path): bool
    {
        $frontname = trim((string) $this->config->get('route_ownership.admin_frontname', 'admin'), '/');

        return $path === $frontname || str_starts_with($path, $frontname.'/');
    }

    private function hasFormKey(Request $request): bool
    {
        return $request->has('form_key')
            || $request->has('_form_key')
            || $request->headers->has('X-Magento-Form-Key');
    }

    private function sessionHas(Request $request, string $key): bool
    {
        return $request->hasSession() && $request->session()->has($key);
    }

    /**
     * @return array{customer: string, admin: string}
     */
    private function sessionBoundary(): array
    {
        $boundary = $this->config->get('route_ownership.session_boundary', []);

        return [
            'customer' => is_array($boundary) ? (string) ($boundary['customer'] ?? 'explicit_non_sharing') : 'explicit_non_sharing',
            'admin' => is_array($boundary) ? (string) ($boundary['admin'] ?? 'explicit_non_sharing') : 'explicit_non_sharing',
        ];
    }
}
