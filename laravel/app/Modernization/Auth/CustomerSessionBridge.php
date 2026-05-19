<?php

namespace App\Modernization\Auth;

class CustomerSessionBridge
{
    /**
     * @param  array<string, mixed>  $session
     * @return array<string, mixed>
     */
    public function boundary(array $session): array
    {
        return [
            'guard' => 'customer',
            'session_key' => config('auth_compatibility.customer.session_key'),
            'authenticated' => isset($session[(string) config('auth_compatibility.customer.session_key')]),
            'boundary' => config('auth_compatibility.customer.boundary'),
            'persistent_cart' => config('auth_compatibility.customer.persistent_cart'),
            'rollback' => config('auth_compatibility.rollback.strategy'),
        ];
    }
}
