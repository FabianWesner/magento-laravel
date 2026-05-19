<?php

namespace App\Modernization\Routing;

use Illuminate\Http\Request;

final readonly class RouteOwnershipDecision
{
    /**
     * @param  array{customer: string, admin: string}  $sessionBoundary
     */
    public function __construct(
        public RouteOwner $owner,
        public string $feature,
        public string $featureFlag,
        public string $rollback,
        public string $routePattern,
        public string $path,
        public string $method,
        public bool $fallbackEnabled,
        public ?string $storeCode,
        public bool $adminFrontname,
        public bool $formKeyPresent,
        public bool $customerSessionPresent,
        public bool $adminSessionPresent,
        public array $sessionBoundary,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'route' => [
                'owner' => $this->owner->value,
                'feature' => $this->feature,
                'feature_flag' => $this->featureFlag,
                'rollback' => $this->rollback,
                'pattern' => $this->routePattern,
                'path' => $this->path,
                'method' => $this->method,
                'store_code' => $this->storeCode,
                'admin_frontname' => $this->adminFrontname,
            ],
            'fallback' => [
                'enabled' => $this->fallbackEnabled,
                'to_legacy' => $this->owner->canFallbackToLegacy(),
            ],
            'csrf' => [
                'form_key_present' => $this->formKeyPresent,
            ],
            'session' => [
                'customer' => $this->customerSessionPresent,
                'admin' => $this->adminSessionPresent,
                'boundary' => $this->sessionBoundary,
            ],
        ];
    }

    public function legacyUrl(string $legacyBaseUrl, Request $request): string
    {
        return rtrim($legacyBaseUrl, '/').'/'.ltrim($request->getRequestUri(), '/');
    }
}
