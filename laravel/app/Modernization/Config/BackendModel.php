<?php

namespace App\Modernization\Config;

final readonly class BackendModel
{
    public function __construct(private SecretConfig $secrets) {}

    public function beforeSave(string $path, string $value): string
    {
        return $this->secrets->encrypt($path, trim($value));
    }
}
