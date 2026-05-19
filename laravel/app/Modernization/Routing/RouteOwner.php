<?php

namespace App\Modernization\Routing;

enum RouteOwner: string
{
    case Laravel = 'Laravel';
    case Legacy = 'legacy';
    case Bridge = 'bridge';

    public static function fromConfig(string $owner): self
    {
        return match (strtolower($owner)) {
            'laravel' => self::Laravel,
            'bridge' => self::Bridge,
            default => self::Legacy,
        };
    }

    public function canFallbackToLegacy(): bool
    {
        return in_array($this, [self::Legacy, self::Bridge], true);
    }
}
