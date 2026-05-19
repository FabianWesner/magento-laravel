<?php

namespace App\Modernization\Config;

final class EnvOverride
{
    public function get(string $path): ?string
    {
        $overrides = config('scoped_config.env_overrides', []);
        if (! is_array($overrides)) {
            return null;
        }

        $value = $overrides[$path] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
