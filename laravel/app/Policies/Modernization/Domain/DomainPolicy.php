<?php

namespace App\Policies\Modernization\Domain;

use App\Models\User;

class DomainPolicy
{
    public function viewDiagnostics(?User $user): bool
    {
        return in_array($user?->getAttribute('role'), ['full', 'catalog', 'read-only'], true);
    }
}
