<?php

namespace App\Modernization\Modules;

use InvalidArgumentException;

final class ModuleRegistry
{
    /**
     * @param  list<ModuleManifest>  $manifests
     * @param  list<string>  $errors
     */
    public function __construct(
        private readonly array $manifests,
        private readonly array $errors = [],
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $configuredModules
     */
    public static function fromConfig(array $configuredModules): self
    {
        $manifests = [];
        $errors = [];

        foreach ($configuredModules as $index => $configuredModule) {
            if (! is_array($configuredModule)) {
                $errors[] = "Module manifest at index {$index} must be an array.";

                continue;
            }

            try {
                $manifest = ModuleManifest::fromArray($configuredModule);
            } catch (InvalidArgumentException $exception) {
                $errors[] = $exception->getMessage();

                continue;
            }

            if (isset($manifests[$manifest->id])) {
                $errors[] = "Duplicate module manifest id [{$manifest->id}].";

                continue;
            }

            $manifests[$manifest->id] = $manifest;
        }

        foreach ($manifests as $manifest) {
            foreach ($manifest->dependencies as $dependency) {
                if (! isset($manifests[$dependency])) {
                    $errors[] = "Module [{$manifest->id}] depends on missing module [{$dependency}].";
                }
            }
        }

        $errors = array_values(array_unique(array_merge($errors, self::cycleErrors($manifests))));

        return new self(array_values($manifests), $errors);
    }

    /**
     * @return list<ModuleManifest>
     */
    public function all(): array
    {
        return $this->manifests;
    }

    /**
     * @return list<ModuleManifest>
     */
    public function enabled(): array
    {
        return array_values(array_filter(
            $this->manifests,
            fn (ModuleManifest $manifest): bool => $manifest->enabled,
        ));
    }

    public function find(string $id): ?ModuleManifest
    {
        foreach ($this->manifests as $manifest) {
            if ($manifest->id === $id) {
                return $manifest;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function healthy(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array{modules: list<array<string, mixed>>, errors: list<string>}
     */
    public function toArray(): array
    {
        return [
            'modules' => array_map(
                fn (ModuleManifest $manifest): array => $manifest->toArray(),
                $this->manifests,
            ),
            'errors' => $this->errors,
        ];
    }

    /**
     * @param  array<string, ModuleManifest>  $manifests
     * @return list<string>
     */
    private static function cycleErrors(array $manifests): array
    {
        $errors = [];
        $visiting = [];
        $visited = [];
        $path = [];

        foreach (array_keys($manifests) as $id) {
            self::detectCycle($id, $manifests, $visiting, $visited, $path, $errors);
        }

        return $errors;
    }

    /**
     * @param  array<string, ModuleManifest>  $manifests
     * @param  array<string, bool>  $visiting
     * @param  array<string, bool>  $visited
     * @param  list<string>  $path
     * @param  list<string>  $errors
     */
    private static function detectCycle(
        string $id,
        array $manifests,
        array &$visiting,
        array &$visited,
        array &$path,
        array &$errors,
    ): void {
        if (isset($visited[$id])) {
            return;
        }

        if (isset($visiting[$id])) {
            $cycleStart = array_search($id, $path, true);
            $cycle = $cycleStart === false ? [$id] : array_slice($path, $cycleStart);
            $cycle[] = $id;
            $errors[] = 'Module dependency cycle detected: '.implode(' -> ', $cycle).'.';

            return;
        }

        $visiting[$id] = true;
        $path[] = $id;

        foreach ($manifests[$id]->dependencies as $dependency) {
            if (isset($manifests[$dependency])) {
                self::detectCycle($dependency, $manifests, $visiting, $visited, $path, $errors);
            }
        }

        array_pop($path);
        unset($visiting[$id]);
        $visited[$id] = true;
    }
}
