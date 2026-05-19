<?php

namespace App\Policies;

use Illuminate\Contracts\Auth\Authenticatable;

final class LegacyApiContractPolicy
{
    public function viewAny(?Authenticatable $user = null): bool
    {
        return true;
    }

    public function view(?Authenticatable $user = null): bool
    {
        return true;
    }
}
