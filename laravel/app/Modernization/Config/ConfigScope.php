<?php

namespace App\Modernization\Config;

enum ConfigScope: string
{
    case Default = 'default';
    case Website = 'websites';
    case Store = 'stores';

    public function label(): string
    {
        return match ($this) {
            self::Default => 'Default Scope',
            self::Website => 'Website Scope',
            self::Store => 'Store Scope',
        };
    }
}
