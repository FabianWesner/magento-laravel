<?php

namespace App\Modernization\Modules;

use InvalidArgumentException;

final class ModuleManifest
{
    /**
     * @param  list<string>  $featureIds
     * @param  list<string>  $dependencies
     * @param  list<class-string>  $providers
     * @param  list<string>  $routes
     * @param  list<string>  $commands
     * @param  list<string>  $events
     * @param  list<string>  $listeners
     * @param  list<string>  $permissions
     * @param  list<string>  $config
     * @param  list<string>  $views
     * @param  list<string>  $jobs
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $version,
        public readonly bool $enabled,
        public readonly string $owner,
        public readonly array $featureIds,
        public readonly array $dependencies,
        public readonly array $providers,
        public readonly array $routes,
        public readonly array $commands,
        public readonly array $events,
        public readonly array $listeners,
        public readonly array $permissions,
        public readonly array $config,
        public readonly array $views,
        public readonly array $jobs,
    ) {
        if (! preg_match('/^[a-z][a-z0-9_-]*$/', $id)) {
            throw new InvalidArgumentException("Module manifest id [{$id}] must be a stable lowercase identifier.");
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    public static function fromArray(array $manifest): self
    {
        $id = self::requiredString($manifest, 'id');

        return new self(
            id: $id,
            name: self::requiredString($manifest, 'name'),
            version: self::requiredString($manifest, 'version'),
            enabled: (bool) ($manifest['enabled'] ?? false),
            owner: self::requiredString($manifest, 'owner'),
            featureIds: self::stringList($manifest, 'feature_ids'),
            dependencies: self::stringList($manifest, 'dependencies'),
            providers: self::stringList($manifest, 'providers'),
            routes: self::stringList($manifest, 'routes'),
            commands: self::stringList($manifest, 'commands'),
            events: self::stringList($manifest, 'events'),
            listeners: self::stringList($manifest, 'listeners'),
            permissions: self::stringList($manifest, 'permissions'),
            config: self::stringList($manifest, 'config'),
            views: self::stringList($manifest, 'views'),
            jobs: self::stringList($manifest, 'jobs'),
        );
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     version: string,
     *     enabled: bool,
     *     owner: string,
     *     feature_ids: list<string>,
     *     dependencies: list<string>,
     *     providers: list<class-string>,
     *     routes: list<string>,
     *     commands: list<string>,
     *     events: list<string>,
     *     listeners: list<string>,
     *     permissions: list<string>,
     *     config: list<string>,
     *     views: list<string>,
     *     jobs: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'version' => $this->version,
            'enabled' => $this->enabled,
            'owner' => $this->owner,
            'feature_ids' => $this->featureIds,
            'dependencies' => $this->dependencies,
            'providers' => $this->providers,
            'routes' => $this->routes,
            'commands' => $this->commands,
            'events' => $this->events,
            'listeners' => $this->listeners,
            'permissions' => $this->permissions,
            'config' => $this->config,
            'views' => $this->views,
            'jobs' => $this->jobs,
        ];
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private static function requiredString(array $manifest, string $key): string
    {
        $value = $manifest[$key] ?? null;
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("Module manifest [{$key}] must be a non-empty string.");
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */
    private static function stringList(array $manifest, string $key): array
    {
        $values = $manifest[$key] ?? [];
        if (! is_array($values)) {
            throw new InvalidArgumentException("Module manifest [{$key}] must be an array of strings.");
        }

        $strings = [];
        foreach ($values as $value) {
            if (! is_string($value) || trim($value) === '') {
                throw new InvalidArgumentException("Module manifest [{$key}] must contain only non-empty strings.");
            }

            $strings[] = $value;
        }

        return array_values($strings);
    }
}
