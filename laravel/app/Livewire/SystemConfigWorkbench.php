<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Config\SecretConfig;
use App\Modernization\Config\SourceModel;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Policies\Modernization\Domain\DomainPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class SystemConfigWorkbench extends Component
{
    public string $storeView = '';

    public string $storeId = '';

    public string $query = '';

    public string $state = '';

    public string $group = '';

    public string $section = 'config';

    public string $role = 'catalog';

    public function setSection(string $section): void
    {
        if (! in_array($section, ['config', 'scopes', 'validation', 'secrets'], true)) {
            return;
        }

        $this->section = $section;
    }

    public function clearFilters(): void
    {
        $this->storeView = '';
        $this->storeId = '';
        $this->query = '';
        $this->state = '';
        $this->group = '';
        $this->section = 'config';
    }

    public function render(
        DomainCatalog $catalog,
        DomainQueryService $queryService,
        DomainPolicy $policy,
        SourceModel $sourceModel,
        SecretConfig $secretConfig,
    ): View {
        $this->storeId = $this->allowedStoreId();
        $this->storeView = $this->allowedStoreView();
        $this->state = $this->allowedState();
        $this->group = $this->allowedGroup();
        $this->section = $this->allowedSection();

        $canViewConfig = $this->canViewConfig($policy);
        $filters = $this->snapshotFilters();
        $configSnapshot = $canViewConfig ? $queryService->snapshot('system_config', $filters) : null;
        $scopeSnapshot = $canViewConfig ? $queryService->snapshot('store_scope', $filters) : null;

        $configRows = $this->filterRows($this->decorateConfigRows($this->snapshotRows($configSnapshot)));
        $scopeRows = $this->filterRows($this->decorateScopeRows($this->snapshotRows($scopeSnapshot)));
        $validationRows = $this->validationRows($configRows);
        $secretRows = $this->secretRows($configRows);

        return view('livewire.system-config-workbench', [
            'configFeature' => $catalog->get('system_config'),
            'scopeFeature' => $catalog->get('store_scope'),
            'featureIds' => array_values(array_unique([
                ...$catalog->get('system_config')->featureIds,
                ...$catalog->get('store_scope')->featureIds,
            ])),
            'canViewConfig' => $canViewConfig,
            'configRows' => $configRows,
            'scopeRows' => $scopeRows,
            'validationRows' => $validationRows,
            'secretRows' => $secretRows,
            'currentRows' => $this->currentRows($configRows, $scopeRows, $validationRows, $secretRows),
            'activeFilters' => $this->activeFilters(),
            'sourceModelPlan' => $this->valueSummary($sourceModel->options('catalog/frontend/list_mode')),
            'secretPathConfigured' => $secretConfig->isSecret('payment/gateway/token'),
            'configImplementation' => (string) config('scoped_config.implementation'),
            'snapshotStoreView' => $configSnapshot['store_view'] ?? null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotFilters(): array
    {
        return array_filter([
            'store_id' => $this->allowedStoreId(),
            'store_view' => $this->allowedStoreView(),
        ], fn (mixed $value): bool => $value !== '' && $value !== null);
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return list<array<string, mixed>>
     */
    private function snapshotRows(?array $snapshot): array
    {
        return $snapshot['payload']['rows'] ?? [];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decorateConfigRows(array $rows): array
    {
        return collect($rows)
            ->map(fn (array $row): array => $this->decorateConfigRow($row))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function decorateConfigRow(array $row): array
    {
        $payload = $row['payload'] ?? [];
        $state = (string) ($payload['validation_state'] ?? 'valid');
        $cacheState = (string) ($payload['cache_state'] ?? 'fresh');
        $isInherited = (bool) ($payload['inherited'] ?? false);
        $isSecret = (bool) ($payload['is_secret'] ?? false);
        $isEnvOverride = (bool) ($payload['is_env_override'] ?? false);
        $usesBackendModel = (bool) ($payload['uses_backend_model'] ?? false);
        $usesSourceModel = (bool) ($payload['uses_source_model'] ?? false);
        $fallbackChain = is_array($payload['fallback_chain'] ?? null) ? $payload['fallback_chain'] : [];
        $sourceOptions = is_array($payload['source_options'] ?? null) ? $payload['source_options'] : [];

        return [
            'domain' => 'system_config',
            'domain_label' => 'System config',
            'entity_id' => (int) ($row['entity_id'] ?? 0),
            'store_id' => (int) ($row['store_id'] ?? 0),
            'store_view' => (string) ($row['store_view'] ?? ''),
            'section' => (string) ($payload['section'] ?? ''),
            'group' => (string) ($payload['group'] ?? ''),
            'path' => (string) ($payload['path'] ?? ''),
            'title' => $this->firstString($payload['label'] ?? null, $payload['path'] ?? null, 'Config value'),
            'scope' => (string) ($payload['scope'] ?? ''),
            'scope_label' => $this->firstString($payload['scope_label'] ?? null, $payload['scope'] ?? null),
            'state' => $state,
            'state_label' => Str::headline($state),
            'value' => $this->firstString($payload['effective_value'] ?? null),
            'attempted_value' => $this->firstString($payload['attempted_value'] ?? null),
            'source_options' => $this->valueSummary($sourceOptions),
            'fallback_chain' => $this->valueSummary($fallbackChain),
            'cache_state' => $cacheState,
            'cache_label' => Str::headline($cacheState),
            'summary' => $this->summary($payload),
            'error' => $this->firstString($payload['error'] ?? null),
            'uses_source_model' => $usesSourceModel,
            'uses_backend_model' => $usesBackendModel,
            'is_secret' => $isSecret,
            'is_env_override' => $isEnvOverride,
            'is_inherited' => $isInherited,
            'is_problem' => $state === 'invalid' || ($payload['error'] ?? null) !== null,
            'is_sensitive' => $isSecret || $usesBackendModel || $isEnvOverride,
            'is_cache_state' => in_array($cacheState, ['invalidated_on_save', 'invalidated_on_inherit', 'bypassed'], true),
            'detail' => $this->valueSummary(array_filter([
                'scope' => $payload['scope'] ?? null,
                'source_model' => $usesSourceModel,
                'backend_model' => $usesBackendModel,
                'secret' => $isSecret,
                'env_override' => $isEnvOverride,
                'inherited' => $isInherited,
            ], fn (mixed $value): bool => $value !== null && $value !== false && $value !== '')),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decorateScopeRows(array $rows): array
    {
        return collect($rows)
            ->map(fn (array $row): array => $this->decorateScopeRow($row))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function decorateScopeRow(array $row): array
    {
        $payload = $row['payload'] ?? [];
        $config = is_array($payload['config'] ?? null) ? $payload['config'] : [];

        return [
            'domain' => 'store_scope',
            'domain_label' => 'Store scope',
            'entity_id' => (int) ($row['entity_id'] ?? 0),
            'store_id' => (int) ($row['store_id'] ?? 0),
            'store_view' => (string) ($row['store_view'] ?? ''),
            'section' => 'scope',
            'group' => 'scope',
            'path' => (string) ($payload['store_code'] ?? ''),
            'title' => $this->firstString($payload['store_name'] ?? null, $payload['store_code'] ?? null, 'Store scope'),
            'scope' => 'stores',
            'scope_label' => $this->firstString($payload['store_group_code'] ?? null, $payload['website_code'] ?? null),
            'state' => 'valid',
            'state_label' => 'Valid',
            'value' => $this->valueSummary([
                'locale' => $payload['locale'] ?? null,
                'currency' => $payload['currency'] ?? null,
                'base_url' => $payload['base_url'] ?? null,
            ]),
            'attempted_value' => '',
            'source_options' => '',
            'fallback_chain' => $this->valueSummary(array_keys($config)),
            'cache_state' => 'profile',
            'cache_label' => 'Profile',
            'summary' => $this->firstString(
                'Store view profile controls locale, currency, URLs, CMS defaults, and inherited feature flags',
            ),
            'error' => '',
            'uses_source_model' => false,
            'uses_backend_model' => false,
            'is_secret' => false,
            'is_env_override' => false,
            'is_inherited' => ! (bool) ($payload['can_use_default'] ?? false),
            'is_problem' => false,
            'is_sensitive' => false,
            'is_cache_state' => false,
            'detail' => $this->valueSummary(array_filter([
                'website' => $payload['website_code'] ?? null,
                'store_group' => $payload['store_group_code'] ?? null,
                'home_page' => $payload['cms_home_page'] ?? null,
                'no_route' => $payload['cms_no_route'] ?? null,
                'default_title' => $payload['default_title'] ?? null,
            ], fn (mixed $value): bool => $value !== null && $value !== '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function summary(array $payload): string
    {
        $ui = $this->nestedArray($payload, 'ui');

        return $this->firstString(
            $ui['summary'] ?? null,
            $payload['summary'] ?? null,
            $payload['path'] ?? null,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function validationRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row): bool => $row['state'] === 'invalid' || $row['uses_source_model'] || $row['is_inherited'])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function secretRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row): bool => $row['is_secret'] || $row['uses_backend_model'] || $row['is_env_override'] || $row['is_cache_state'])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function filterRows(array $rows): array
    {
        $query = Str::lower(trim(Str::substr($this->query, 0, 128)));
        $state = $this->allowedState();
        $group = $this->allowedGroup();

        return collect($rows)
            ->filter(function (array $row) use ($query, $state, $group): bool {
                if ($state !== '' && ! $this->matchesState($row, $state)) {
                    return false;
                }

                if ($group !== '' && ! $this->matchesGroup($row, $group)) {
                    return false;
                }

                if ($query === '') {
                    return true;
                }

                return Str::contains($this->rowHaystack($row), $query);
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function matchesState(array $row, string $state): bool
    {
        return match ($state) {
            'invalid' => $row['state'] === 'invalid' || $row['is_problem'],
            'inherited' => $row['is_inherited'] || $row['state'] === 'inherited',
            'secret' => $row['is_secret'] || $row['uses_backend_model'],
            'env_override' => $row['is_env_override'],
            'cache' => $row['is_cache_state'],
            default => $row['state'] === $state,
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function matchesGroup(array $row, string $group): bool
    {
        if ($group === 'scope') {
            return $row['domain'] === 'store_scope';
        }

        return $row['section'] === $group || $row['group'] === $group || Str::startsWith((string) $row['path'], "{$group}/");
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowHaystack(array $row): string
    {
        return Str::lower(implode(' ', array_filter([
            $row['domain_label'] ?? '',
            $row['title'] ?? '',
            $row['state_label'] ?? '',
            $row['cache_label'] ?? '',
            $row['section'] ?? '',
            $row['group'] ?? '',
            $row['path'] ?? '',
            $row['scope_label'] ?? '',
            $row['value'] ?? '',
            $row['attempted_value'] ?? '',
            $row['source_options'] ?? '',
            $row['fallback_chain'] ?? '',
            $row['summary'] ?? '',
            $row['error'] ?? '',
            $row['detail'] ?? '',
        ])));
    }

    /**
     * @param  list<array<string, mixed>>  $configRows
     * @param  list<array<string, mixed>>  $scopeRows
     * @param  list<array<string, mixed>>  $validationRows
     * @param  list<array<string, mixed>>  $secretRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $configRows, array $scopeRows, array $validationRows, array $secretRows): array
    {
        return match ($this->allowedSection()) {
            'scopes' => $scopeRows,
            'validation' => $validationRows,
            'secrets' => $secretRows,
            default => $configRows,
        };
    }

    /**
     * @return array<string, string>
     */
    private function activeFilters(): array
    {
        return array_filter([
            'store' => $this->allowedStoreView(),
            'store_id' => $this->allowedStoreId(),
            'query' => trim(Str::substr($this->query, 0, 128)),
            'state' => $this->allowedState(),
            'group' => $this->allowedGroup(),
        ], fn (string $value): bool => $value !== '');
    }

    private function allowedStoreId(): string
    {
        return in_array($this->storeId, ['', '9001', '9002'], true) ? $this->storeId : '';
    }

    private function allowedStoreView(): string
    {
        return in_array($this->storeView, ['', 'default', 'de'], true) ? $this->storeView : '';
    }

    private function allowedState(): string
    {
        return in_array($this->state, ['', 'valid', 'invalid', 'inherited', 'secret', 'env_override', 'cache'], true) ? $this->state : '';
    }

    private function allowedGroup(): string
    {
        return in_array($this->group, ['', 'web', 'catalog', 'payment', 'general', 'currency', 'scope'], true) ? $this->group : '';
    }

    private function allowedSection(): string
    {
        return in_array($this->section, ['config', 'scopes', 'validation', 'secrets'], true) ? $this->section : 'config';
    }

    private function canViewConfig(DomainPolicy $policy): bool
    {
        if (! app()->environment(['local', 'testing'])) {
            return false;
        }

        return $policy->viewDiagnostics($this->fixtureUser());
    }

    private function fixtureUser(): User
    {
        $user = new User;
        $user->forceFill([
            'id' => 919,
            'name' => "{$this->role} system config fixture",
            'email' => "{$this->role}-system-config@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function nestedArray(array $payload, string $key): array
    {
        return is_array($payload[$key] ?? null) ? $payload[$key] : [];
    }

    private function firstString(mixed ...$values): string
    {
        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                $value = $this->valueSummary($value);
            }

            $stringValue = trim((string) $value);

            if ($stringValue !== '') {
                return $stringValue;
            }
        }

        return '';
    }

    private function valueSummary(mixed $value): string
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $nestedValue, string|int $nestedKey): string => is_int($nestedKey)
                    ? $this->valueSummary($nestedValue)
                    : Str::headline((string) $nestedKey).': '.$this->valueSummary($nestedValue))
                ->filter()
                ->implode(' / ');
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        return (string) $value;
    }
}
