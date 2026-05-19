<?php

namespace App\Modernization\Bootstrap;

use App\Modernization\Bootstrap\Contracts\HealthCheck;
use Illuminate\Auth\AuthManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Session\SessionManager;
use Illuminate\Translation\Translator;

final readonly class CoreInfrastructure
{
    /**
     * @param  iterable<HealthCheck>  $healthChecks
     */
    public function __construct(
        private ConfigRepository $config,
        private DatabaseManager $database,
        private CacheManager $cache,
        private SessionManager $session,
        private Dispatcher $events,
        private FilesystemManager $filesystem,
        private UrlGenerator $url,
        private AuthManager $auth,
        private Translator $translator,
        private iterable $healthChecks,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return [
            'config' => $this->config->get('app.name'),
            'database' => $this->database->getDefaultConnection(),
            'cache' => $this->cache->getDefaultDriver(),
            'session' => $this->session->getDefaultDriver(),
            'events' => $this->events::class,
            'filesystem' => $this->filesystem->getDefaultDriver(),
            'URL' => $this->url->to('/'),
            'auth' => $this->auth->getDefaultDriver(),
            'translation' => $this->translator->getLocale(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function health(): array
    {
        $reports = [];

        foreach ($this->healthChecks as $healthCheck) {
            $reports[$healthCheck->name()] = $healthCheck->report();
        }

        return $reports;
    }
}
