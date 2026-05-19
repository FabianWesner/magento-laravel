<?php

namespace App\Modernization\Auth;

class AdminSessionBridge
{
    /**
     * @param  array<string, mixed>  $session
     * @return array<string, mixed>
     */
    public function boundary(array $session, string $role): array
    {
        return [
            'guard' => 'admin',
            'session_key' => config('auth_compatibility.admin.session_key'),
            'authenticated' => isset($session[(string) config('auth_compatibility.admin.session_key')]),
            'boundary' => config('auth_compatibility.admin.boundary'),
            'timeout_minutes' => config('auth_compatibility.admin.timeout_minutes'),
            'role' => $role,
            'permissions' => app(PermissionManifest::class)->forRole($role),
        ];
    }
}
