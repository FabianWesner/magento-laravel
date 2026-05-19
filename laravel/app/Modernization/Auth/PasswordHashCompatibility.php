<?php

namespace App\Modernization\Auth;

use Illuminate\Support\Facades\Hash;

class PasswordHashCompatibility
{
    /**
     * @return array{verified: bool, algorithm: string, needs_rehash: bool, broker: string}
     */
    public function verifyAndPlanUpgrade(string $plainPassword, string $storedHash): array
    {
        if (str_contains($storedHash, ':')) {
            [$hash, $salt] = explode(':', $storedHash, 2);
            $verified = hash_equals($hash, md5($salt.$plainPassword));

            return [
                'verified' => $verified,
                'algorithm' => 'legacy password salted-md5',
                'needs_rehash' => $verified,
                'broker' => 'PasswordBroker customers/admins',
            ];
        }

        $verified = Hash::check($plainPassword, $storedHash);

        return [
            'verified' => $verified,
            'algorithm' => 'laravel',
            'needs_rehash' => $verified && Hash::needsRehash($storedHash),
            'broker' => 'PasswordBroker customers/admins',
        ];
    }
}
