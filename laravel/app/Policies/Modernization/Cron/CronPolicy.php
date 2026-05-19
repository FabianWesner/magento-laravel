<?php

namespace App\Policies\Modernization\Cron;

use App\Models\User;

class CronPolicy
{
    public function view(?User $user): bool
    {
        return in_array($user?->getAttribute('role'), ['full', 'read-only', 'scheduler'], true);
    }
}
