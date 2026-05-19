<?php

namespace App\Modernization\Config;

use Illuminate\Support\Facades\Crypt;

final class SecretConfig
{
    public function isSecret(string $path): bool
    {
        $paths = config('scoped_config.secret_paths', []);

        return is_array($paths) && in_array($path, $paths, true);
    }

    public function encrypt(string $path, string $value): string
    {
        return $this->isSecret($path) ? Crypt::encryptString($value) : $value;
    }

    public function decrypt(string $path, string $value): string
    {
        if (! $this->isSecret($path)) {
            return $value;
        }

        return Crypt::decryptString($value);
    }
}
