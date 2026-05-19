<?php

namespace App\Policies\Modernization\Integrations;

use App\Models\User;

class IntegrationPolicy
{
    public function view(?User $user): bool
    {
        return in_array($user?->getAttribute('role'), ['full', 'read-only', 'integrations'], true);
    }
}
