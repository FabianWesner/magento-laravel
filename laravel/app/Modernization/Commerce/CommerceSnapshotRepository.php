<?php

namespace App\Modernization\Commerce;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommerceSnapshotRepository
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(string $featureKey, string $snapshotType, array $payload): void
    {
        DB::table('commerce_facts')->insert([
            'feature_key' => $featureKey,
            'snapshot_type' => $snapshotType,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
        ]);
    }

    /**
     * @return Collection<int, object>
     */
    public function snapshots(string $featureKey): Collection
    {
        return DB::table('commerce_facts')
            ->where('feature_key', $featureKey)
            ->orderBy('id')
            ->get();
    }
}
