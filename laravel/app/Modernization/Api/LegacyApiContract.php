<?php

namespace App\Modernization\Api;

final readonly class LegacyApiContract
{
    /**
     * @param  list<string>  $roles
     */
    public function __construct(
        public string $featureId,
        public string $name,
        public string $protocol,
        public string $auth,
        public array $roles,
        public string $legacyEndpoint,
        public string $status,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'feature_id' => $this->featureId,
            'name' => $this->name,
            'protocol' => $this->protocol,
            'auth' => $this->auth,
            'roles' => $this->roles,
            'legacy_endpoint' => $this->legacyEndpoint,
            'status' => $this->status,
            'error_format' => [
                'code' => 'string',
                'message' => 'string',
                'status' => 'integer',
            ],
        ];
    }
}
