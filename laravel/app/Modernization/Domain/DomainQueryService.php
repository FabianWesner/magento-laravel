<?php

namespace App\Modernization\Domain;

use App\Modernization\Domain\Contracts\DomainServiceContract;

class DomainQueryService implements DomainServiceContract
{
    public function __construct(
        private readonly DomainCatalog $catalog,
        private readonly DomainRepository $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function snapshot(string $key, array $filters = []): array
    {
        $feature = $this->catalog->get($key);
        $rows = $this->repository
            ->forFeature($key, $filters)
            ->map(fn (object $row): array => [
                'entity_id' => (int) $row->entity_id,
                'store_id' => (int) $row->store_id,
                'store_view' => (string) $row->store_view,
                'payload' => json_decode((string) $row->payload, true),
            ])
            ->all();

        return (new DomainFeatureValueObject($feature, [
            'rows' => $rows,
            'count' => count($rows),
            'DB snapshot' => $filters,
            'database delta' => [],
        ], $filters['store_view'] ?? null))->toArray();
    }
}
