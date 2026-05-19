<?php

namespace Tests\Feature;

use App\Modernization\Schema\SchemaPreservationPolicy;
use App\Modernization\Schema\SchemaSnapshotRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaPreservationFoundationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->app->make(SchemaPreservationPolicy::class)->snapshotTables() as $table) {
            Schema::dropIfExists($table);
            Schema::create($table, function (Blueprint $table): void {
                $table->id();
                $table->unsignedInteger('entity_id')->nullable();
                $table->unsignedInteger('attribute_id')->nullable();
                $table->unsignedInteger('store_id')->default(0);
                $table->string('value')->nullable();
            });
        }

        DB::table('catalog_product_entity')->insert(['entity_id' => 1, 'value' => 'simple product sample data']);
        DB::table('catalog_category_entity')->insert(['entity_id' => 2, 'value' => 'root category sample data']);
        DB::table('customer_entity')->insert(['entity_id' => 3, 'value' => 'sample customer']);
        DB::table('customer_address_entity')->insert(['entity_id' => 4, 'value' => 'sample address']);
        DB::table('sales_flat_order')->insert(['entity_id' => 5, 'value' => 'sample order']);
        DB::table('core_config_data')->insert(['attribute_id' => 6, 'value' => 'project data base URL']);
        DB::table('eav_attribute')->insert(['attribute_id' => 7, 'value' => 'name']);
        DB::table('catalog_product_entity_varchar')->insert(['entity_id' => 1, 'attribute_id' => 7, 'store_id' => 0, 'value' => 'Default product name']);
        DB::table('catalog_product_entity_int')->insert(['entity_id' => 1, 'attribute_id' => 8, 'store_id' => 0, 'value' => '1']);
        DB::table('customer_entity_datetime')->insert(['entity_id' => 3, 'attribute_id' => 9, 'store_id' => 0, 'value' => '2026-05-19 00:00:00']);
    }

    public function test_schema_checksum_before_and_after_laravel_boot_records_core_entity_row_count_snapshots(): void
    {
        $policy = $this->app->make(SchemaPreservationPolicy::class);
        $repository = $this->app->make(SchemaSnapshotRepository::class);

        $before = $repository->snapshot($policy->snapshotTables());
        $after = $repository->snapshot($policy->snapshotTables());

        $this->assertSame($before->schemaSignature, $after->schemaSignature, 'schema checksum before and after Laravel boot stays stable');
        $this->assertSame(1, $after->rowCount('catalog_product_entity'));
        $this->assertSame(1, $after->rowCount('catalog_category_entity'));
        $this->assertSame(1, $after->rowCount('customer_entity'));
        $this->assertSame(1, $after->rowCount('sales_flat_order'));
        $this->assertSame(1, $after->rowCount('core_config_data'));
    }

    public function test_eav_data_signatures_and_original_seed_sample_project_data_are_preserved(): void
    {
        $snapshot = $this->app->make(SchemaSnapshotRepository::class)
            ->snapshot($this->app->make(SchemaPreservationPolicy::class)->snapshotTables());

        $this->assertArrayHasKey('catalog_product_entity_varchar', $snapshot->eavTableSignatures);
        $this->assertArrayHasKey('catalog_product_entity_int', $snapshot->eavTableSignatures);
        $this->assertArrayHasKey('customer_entity_datetime', $snapshot->eavTableSignatures);
        $this->assertNotNull($snapshot->tableSignature('eav_attribute'));
        $this->assertSame('project data base URL', DB::table('core_config_data')->value('value'), 'original seed sample and project data compatibility');
    }

    public function test_policy_blocks_destructive_migration_against_commerce_schema_and_eav_tables(): void
    {
        $policy = $this->app->make(SchemaPreservationPolicy::class);

        $this->assertFalse($policy->allowsMigrationOperation('catalog_product_entity', 'dropColumn'), 'destructive migration against commerce schema is blocked');
        $this->assertFalse($policy->allowsMigrationOperation('catalog_product_entity_varchar', 'Schema::dropIfExists'), 'destructive migration against EAV table is blocked');
        $this->assertFalse($policy->allowsMigrationOperation('sales_flat_order', 'renameColumn'));
        $this->assertTrue($policy->isApprovedInfrastructureTable('modernization_snapshots'), 'approved infrastructure table remains isolated from Magento tables');
        $this->assertTrue($policy->allowsMigrationOperation('modernization_snapshots', 'Schema::create'));
    }

    public function test_fixture_restore_locally_and_ci_schema_report_db_delta_and_side_effect_tracking(): void
    {
        $policy = $this->app->make(SchemaPreservationPolicy::class);
        $repository = $this->app->make(SchemaSnapshotRepository::class);
        $before = $repository->snapshot($policy->snapshotTables());

        DB::table('sales_flat_order')->insert(['entity_id' => 6, 'value' => 'side effects characterization order']);

        $after = $repository->snapshot($policy->snapshotTables());
        $delta = $repository->rowCountDelta($before, $after);
        $ownershipPolicy = $policy->databaseOwnershipPolicy();

        $this->assertStringContainsString('fixture-restore-check.sh', $policy->fixtureRestoreCommand('/fixtures/project.sql.gz', '/fixtures/media'));
        $this->assertStringContainsString('schema-report.php', $policy->schemaReportCommand());
        $this->assertStringContainsString('Local and CI fixture restore', $ownershipPolicy['fixture_restore']);
        $this->assertSame(1, $delta['sales_flat_order'], 'DB delta side effects characterization is tracked before and after fixture behavior');
        $this->assertSame(0, $delta['catalog_product_entity']);
    }

    public function test_database_ownership_policy_names_schema_preservation_and_checksum_boundaries(): void
    {
        $policy = $this->app->make(SchemaPreservationPolicy::class)->databaseOwnershipPolicy();

        $this->assertStringContainsString('DatabaseOwnership', $policy['database_ownership']);
        $this->assertStringContainsString('SchemaPreservation', $policy['schema_preservation']);
        $this->assertStringContainsString('schema-report.php checksums', $policy['fixture_restore']);
        $this->assertStringContainsString('catalog_product_entity', $policy['core_entity_snapshot']);
        $this->assertStringContainsString('catalog_product_entity_varchar', $policy['eav_signature']);
    }
}
