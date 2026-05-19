<?php

namespace App\Policies\Modernization\Auth;

use App\Models\User;

class AdminPermissionPolicy
{
    public function view(?User $user): bool
    {
        return in_array($user?->getAttribute('role'), ['full', 'read-only', 'security'], true);
    }
}
