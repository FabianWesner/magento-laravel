<?php

namespace App\Modernization\Schema;

class SchemaSnapshot
{
    /**
     * @param  array<string, array{columns: list<string>, row_count: int, signature: string}>  $tables
     * @param  array<string, string>  $eavTableSignatures
     */
    public function __construct(
        public readonly array $tables,
        public readonly array $eavTableSignatures,
        public readonly string $schemaSignature,
    ) {}

    public function rowCount(string $table): int
    {
        return $this->tables[$table]['row_count'] ?? 0;
    }

    public function tableSignature(string $table): ?string
    {
        return $this->tables[$table]['signature'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tables' => $this->tables,
            'eav_table_signatures' => $this->eavTableSignatures,
            'schema_signature' => $this->schemaSignature,
        ];
    }
}
