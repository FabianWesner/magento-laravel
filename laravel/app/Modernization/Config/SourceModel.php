<?php

namespace App\Modernization\Config;

final class SourceModel
{
    public function allows(string $path, string $value): bool
    {
        $options = $this->options($path);

        return $options === [] || in_array($value, $options, true);
    }

    /**
     * @return list<string>
     */
    public function options(string $path): array
    {
        $sourceModels = config('scoped_config.source_models', []);
        if (! is_array($sourceModels) || ! is_array($sourceModels[$path] ?? null)) {
            return [];
        }

        return array_values(array_map('strval', $sourceModels[$path]));
    }
}
