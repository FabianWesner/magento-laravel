<?php

namespace App\Modernization\Schema;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchemaSnapshotRepository
{
    /**
     * @param  list<string>  $tables
     */
    public function snapshot(array $tables): SchemaSnapshot
    {
        $tableMetadata = [];
        $eavTableSignatures = [];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            sort($columns);

            $signature = hash('sha256', $table.'|'.implode(',', $columns));
            $tableMetadata[$table] = [
                'columns' => array_values($columns),
                'row_count' => (int) DB::table($table)->count(),
                'signature' => $signature,
            ];

            if ($this->isEavSignatureTable($table)) {
                $eavTableSignatures[$table] = $signature;
            }
        }

        ksort($tableMetadata);
        ksort($eavTableSignatures);

        return new SchemaSnapshot(
            tables: $tableMetadata,
            eavTableSignatures: $eavTableSignatures,
            schemaSignature: $this->schemaSignature($tableMetadata),
        );
    }

    /**
     * @return array<string, int>
     */
    public function rowCountDelta(SchemaSnapshot $before, SchemaSnapshot $after): array
    {
        $tables = array_unique(array_merge(array_keys($before->tables), array_keys($after->tables)));
        sort($tables);

        $delta = [];
        foreach ($tables as $table) {
            $delta[$table] = $after->rowCount($table) - $before->rowCount($table);
        }

        return $delta;
    }

    private function isEavSignatureTable(string $table): bool
    {
        return str_starts_with($table, 'eav_')
            || str_contains($table, '_entity_int')
            || str_contains($table, '_entity_varchar')
            || str_contains($table, '_entity_decimal')
            || str_contains($table, '_entity_text')
            || str_contains($table, '_entity_datetime');
    }

    /**
     * @param  array<string, array{columns: list<string>, row_count: int, signature: string}>  $tableMetadata
     */
    private function schemaSignature(array $tableMetadata): string
    {
        $tableSignatures = array_map(
            fn (array $table): string => $table['signature'],
            $tableMetadata,
        );

        return hash('sha256', json_encode($tableSignatures, JSON_THROW_ON_ERROR));
    }
}
