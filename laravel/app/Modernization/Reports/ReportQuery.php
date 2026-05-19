<?php

namespace App\Modernization\Reports;

use Illuminate\Support\Facades\DB;

class ReportQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function run(ReportDefinition $definition, array $filters = []): ReportResult
    {
        $query = DB::table($definition->table)
            ->selectRaw("{$definition->groupColumn} as bucket, currency, sum({$definition->aggregateColumn}) as total, count(*) as row_count, avg({$definition->aggregateColumn}) as average")
            ->where('report_key', $definition->key);

        if (($filters['from_date'] ?? null) !== null) {
            $query->where('reported_at', '>=', $filters['from_date']);
        }

        if (($filters['to_date'] ?? null) !== null) {
            $query->where('reported_at', '<=', $filters['to_date']);
        }

        if (($filters['store_id'] ?? null) !== null) {
            $query->where('store_id', $filters['store_id']);
        }

        if (($filters['currency'] ?? null) !== null) {
            $query->where('currency', $filters['currency']);
        }

        $rows = $query
            ->groupBy($definition->groupColumn, 'currency')
            ->orderBy($definition->groupColumn)
            ->get()
            ->map(fn (object $row): array => [
                'bucket' => (string) $row->bucket,
                'currency' => (string) $row->currency,
                'total' => (float) $row->total,
                'count' => (int) $row->row_count,
                'average' => (float) $row->average,
            ])
            ->all();

        return new ReportResult(
            definition: $definition,
            rows: $rows,
            total: (float) collect($rows)->sum('total'),
            count: (int) collect($rows)->sum('count'),
            currency: $filters['currency'] ?? ($rows[0]['currency'] ?? null),
            filters: $filters,
        );
    }

    public function exportCsv(ReportResult $result): string
    {
        return $result->toCsv();
    }
}
