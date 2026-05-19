<?php

namespace App\Modernization\Schema;

class SchemaPreservationPolicy
{
    /**
     * @return list<string>
     */
    public function coreCommerceTables(): array
    {
        return [
            'catalog_product_entity',
            'catalog_category_entity',
            'customer_entity',
            'customer_address_entity',
            'sales_flat_order',
            'sales_flat_quote',
            'core_config_data',
            'eav_attribute',
        ];
    }

    /**
     * @return list<string>
     */
    public function eavSignatureTables(): array
    {
        return [
            'catalog_product_entity_int',
            'catalog_product_entity_varchar',
            'catalog_product_entity_decimal',
            'catalog_product_entity_text',
            'catalog_product_entity_datetime',
            'catalog_category_entity_int',
            'catalog_category_entity_varchar',
            'customer_entity_int',
            'customer_entity_varchar',
            'customer_entity_datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public function snapshotTables(): array
    {
        return array_values(array_unique(array_merge(
            $this->coreCommerceTables(),
            $this->eavSignatureTables(),
        )));
    }

    /**
     * @return list<string>
     */
    public function approvedInfrastructureTables(): array
    {
        return [
            'users',
            'cache',
            'jobs',
            'job_batches',
            'failed_jobs',
            'commerce_facts',
            'modernization_snapshots',
        ];
    }

    public function allowsMigrationOperation(string $table, string $operation): bool
    {
        if (! $this->isCommerceSchemaTable($table)) {
            return true;
        }

        return ! in_array($operation, $this->destructiveMigrationOperations(), true);
    }

    public function isApprovedInfrastructureTable(string $table): bool
    {
        return in_array($table, $this->approvedInfrastructureTables(), true);
    }

    public function fixtureRestoreCommand(string $fixture, ?string $media = null): string
    {
        $command = "bash dev/modernization/fixture-restore-check.sh --fixture={$fixture}";

        if ($media !== null) {
            $command .= " --media={$media}";
        }

        return $command;
    }

    public function schemaReportCommand(): string
    {
        return 'DB_DSN=<fixture> php dev/modernization/schema-report.php --format=markdown';
    }

    /**
     * @return array<string, string>
     */
    public function databaseOwnershipPolicy(): array
    {
        return [
            'database_ownership' => 'DatabaseOwnership: preserve existing commerce tables and EAV schema as source of truth',
            'schema_preservation' => 'SchemaPreservation policy forbids destructive migration operations on Magento commerce schema tables',
            'infrastructure_tables' => 'Approved infrastructure table migrations may use Schema::create only outside preserved commerce tables',
            'fixture_restore' => 'Local and CI fixture restore paths use fixture-restore-check.sh and schema-report.php checksums',
            'core_entity_snapshot' => 'Core entity row count snapshot covers catalog_product_entity, catalog_category_entity, customer_entity, sales_flat_order, and core_config_data',
            'eav_signature' => 'EAV signature covers catalog_product_entity_varchar and related catalog/customer EAV value tables',
        ];
    }

    /**
     * @return list<string>
     */
    private function destructiveMigrationOperations(): array
    {
        return [
            'Schema::drop',
            'Schema::dropIfExists',
            'Schema::rename',
            'drop',
            'dropColumn',
            'renameColumn',
            'change',
        ];
    }

    private function isCommerceSchemaTable(string $table): bool
    {
        return in_array($table, $this->snapshotTables(), true)
            || str_starts_with($table, 'sales_flat_')
            || str_starts_with($table, 'cataloginventory_')
            || str_starts_with($table, 'catalogrule')
            || str_starts_with($table, 'salesrule')
            || str_starts_with($table, 'tax_')
            || str_starts_with($table, 'quote')
            || str_starts_with($table, 'api')
            || str_starts_with($table, 'oauth');
    }
}
