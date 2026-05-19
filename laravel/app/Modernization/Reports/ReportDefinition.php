<?php

namespace App\Modernization\Reports;

class ReportDefinition
{
    /**
     * @param  list<string>  $featureIds
     * @param  list<string>  $filters
     */
    public function __construct(
        public readonly string $key,
        public readonly string $context,
        public readonly string $label,
        public readonly array $featureIds,
        public readonly string $table,
        public readonly string $aggregateColumn,
        public readonly string $groupColumn,
        public readonly array $filters,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'context' => $this->context,
            'label' => $this->label,
            'feature_ids' => $this->featureIds,
            'table' => $this->table,
            'aggregate_column' => $this->aggregateColumn,
            'group_column' => $this->groupColumn,
            'filters' => $this->filters,
        ];
    }
}
