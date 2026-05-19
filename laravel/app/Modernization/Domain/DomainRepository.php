<?php

namespace App\Modernization\Domain;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DomainRepository
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object>
     */
    public function forFeature(string $key, array $filters = []): Collection
    {
        $query = DB::table('domain_facts')->where('feature_key', $key);

        if (($filters['store_id'] ?? null) !== null) {
            $query->where('store_id', $filters['store_id']);
        }

        if (($filters['store_view'] ?? null) !== null) {
            $query->where('store_view', $filters['store_view']);
        }

        return $query->orderBy('entity_id')->get();
    }
}
