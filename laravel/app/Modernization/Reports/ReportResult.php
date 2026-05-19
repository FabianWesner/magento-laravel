<?php

namespace App\Modernization\Reports;

class ReportResult
{
    public const KIND = 'ReportResultValueObject';

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public readonly ReportDefinition $definition,
        public readonly array $rows,
        public readonly float $total,
        public readonly int $count,
        public readonly ?string $currency,
        public readonly array $filters,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'report' => $this->definition->toArray(),
            'rows' => $this->rows,
            'total' => $this->total,
            'count' => $this->count,
            'currency' => $this->currency,
            'filters' => $this->filters,
            'grid' => [
                'pagination' => ['page' => 1, 'per_page' => 50],
                'sort' => ['bucket' => 'asc'],
            ],
        ];
    }

    public function toCsv(): string
    {
        $lines = ['bucket,currency,total,count,average'];

        foreach ($this->rows as $row) {
            $lines[] = implode(',', [
                $row['bucket'] ?? '',
                $row['currency'] ?? '',
                $row['total'] ?? 0,
                $row['count'] ?? 0,
                $row['average'] ?? 0,
            ]);
        }

        return implode("\n", $lines);
    }
}
