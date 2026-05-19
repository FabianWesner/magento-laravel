<?php

namespace App\Policies\Modernization\Reports;

use App\Models\User;

class ReportPolicy
{
    public function view(?User $user): bool
    {
        return in_array($user?->getAttribute('role'), ['full', 'read-only', 'reports'], true);
    }
}
