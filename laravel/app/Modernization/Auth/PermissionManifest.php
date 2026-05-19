<?php

namespace App\Modernization\Auth;

class PermissionManifest
{
    /**
     * @return array<string, list<string>>
     */
    public function permissions(): array
    {
        return [
            'full' => [
                'admin.dashboard',
                'catalog.products',
                'sales.orders',
                'reports.read',
                'api.oauth',
            ],
            'partial' => [
                'catalog.products',
                'reports.read',
            ],
            'read-only' => [
                'reports.read',
            ],
            'denied' => [],
        ];
    }

    /**
     * @return list<string>
     */
    public function forRole(string $role): array
    {
        return $this->permissions()[$role] ?? [];
    }

    public function allows(string $role, string $permission): bool
    {
        return in_array($permission, $this->forRole($role), true);
    }
}
