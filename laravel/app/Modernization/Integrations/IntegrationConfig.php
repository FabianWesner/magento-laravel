<?php

namespace App\Modernization\Integrations;

use InvalidArgumentException;

class IntegrationConfig
{
    /**
     * @return array<string, IntegrationEndpoint>
     */
    public function all(): array
    {
        $adapters = config('integrations.adapters', []);

        if (! is_array($adapters)) {
            return [];
        }

        $endpoints = [];

        foreach ($adapters as $key => $adapter) {
            if (is_string($key) && is_array($adapter)) {
                $endpoints[$key] = IntegrationEndpoint::fromArray($key, $adapter);
            }
        }

        return $endpoints;
    }

    public function adapter(string $key): IntegrationEndpoint
    {
        $adapter = config("integrations.adapters.{$key}");

        if (! is_array($adapter)) {
            throw new InvalidArgumentException("Integration adapter [{$key}] is not configured.");
        }

        return IntegrationEndpoint::fromArray($key, $adapter);
    }
}
