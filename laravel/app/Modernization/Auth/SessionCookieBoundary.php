<?php

namespace App\Modernization\Auth;

class SessionCookieBoundary
{
    /**
     * @return array<string, mixed>
     */
    public function cookie(): array
    {
        return [
            'name' => config('auth_compatibility.cookie.name'),
            'domain' => config('auth_compatibility.cookie.domain'),
            'path' => config('auth_compatibility.cookie.path'),
            'secure_flag' => config('auth_compatibility.cookie.secure'),
            'same-site' => config('auth_compatibility.cookie.same_site'),
            'logout_invalidation' => config('auth_compatibility.cookie.logout_invalidation'),
            'rollback' => config('auth_compatibility.rollback.strategy'),
        ];
    }

    /**
     * @return array{invalidate: bool, regenerate: bool, rollback: mixed}
     */
    public function logoutInvalidation(): array
    {
        return [
            'invalidate' => true,
            'regenerate' => true,
            'rollback' => config('auth_compatibility.rollback.strategy'),
        ];
    }
}
