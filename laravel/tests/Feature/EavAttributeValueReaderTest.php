<?php

namespace Tests\Feature;

use App\Modernization\Eav\Repositories\AddressEavRepository;
use App\Modernization\Eav\Repositories\CategoryEavRepository;
use App\Modernization\Eav\Repositories\CustomerEavRepository;
use App\Modernization\Eav\Repositories\ProductEavRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EavAttributeValueReaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createEavFixtureSchema();
        $this->seedEavFixtureData();
    }

    public function test_product_eav_store_scope_fallback_matches_legacy_resource_model_behavior(): void
    {
        $this->expectsDatabaseQueryCount(2);

        $value = $this->app
            ->make(ProductEavRepository::class)
            ->value(productId: 100, attributeCode: 'name', storeId: 2);

        $this->assertSame('Scoped Product', $value);
    }

    public function test_product_eav_scope_fallback_uses_default_store_value_when_store_value_is_missing(): void
    {
        $value = $this->app
            ->make(ProductEavRepository::class)
            ->value(productId: 100, attributeCode: 'name', storeId: 3);

        $this->assertSame('Default Product', $value);
    }

    public function test_product_eav_static_attribute_reads_from_base_entity_table(): void
    {
        $value = $this->app
            ->make(ProductEavRepository::class)
            ->value(productId: 100, attributeCode: 'sku');

        $this->assertSame('sku-100', $value);
    }

    public function test_category_customer_and_address_eav_repositories_read_supported_entity_values(): void
    {
        $category = $this->app
            ->make(CategoryEavRepository::class)
            ->value(categoryId: 200, attributeCode: 'name', storeId: 5);

        $customer = $this->app
            ->make(CustomerEavRepository::class)
            ->value(customerId: 300, attributeCode: 'firstname');

        $address = $this->app
            ->make(AddressEavRepository::class)
            ->value(addressId: 400, attributeCode: 'city');

        $this->assertSame('Default Category', $category);
        $this->assertSame('Taylor', $customer);
        $this->assertSame('Berlin', $address);
    }

    public function test_missing_eav_attribute_returns_null(): void
    {
        $value = $this->app
            ->make(ProductEavRepository::class)
            ->value(productId: 100, attributeCode: 'missing_attribute');

        $this->assertNull($value);
    }

    private function createEavFixtureSchema(): void
    {
        foreach ($this->fixtureTables() as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('eav_entity_type', function (Blueprint $table): void {
            $table->integer('entity_type_id')->primary();
            $table->string('entity_type_code');
        });

        Schema::create('eav_attribute', function (Blueprint $table): void {
            $table->integer('attribute_id')->primary();
            $table->integer('entity_type_id');
            $table->string('attribute_code');
            $table->string('backend_type');
        });

        Schema::create('catalog_product_entity', function (Blueprint $table): void {
            $table->integer('entity_id')->primary();
            $table->string('sku')->nullable();
        });

        Schema::create('catalog_category_entity', function (Blueprint $table): void {
            $table->integer('entity_id')->primary();
        });

        Schema::create('customer_entity', function (Blueprint $table): void {
            $table->integer('entity_id')->primary();
        });

        Schema::create('customer_address_entity', function (Blueprint $table): void {
            $table->integer('entity_id')->primary();
        });

        $this->createScopedValueTable('catalog_product_entity_varchar');
        $this->createScopedValueTable('catalog_category_entity_varchar');
        $this->createUnscopedValueTable('customer_entity_varchar');
        $this->createUnscopedValueTable('customer_address_entity_varchar');
    }

    private function createScopedValueTable(string $tableName): void
    {
        Schema::create($tableName, function (Blueprint $table): void {
            $table->integer('value_id')->primary();
            $table->integer('attribute_id');
            $table->integer('store_id');
            $table->integer('entity_id');
            $table->string('value')->nullable();
        });
    }

    private function createUnscopedValueTable(string $tableName): void
    {
        Schema::create($tableName, function (Blueprint $table): void {
            $table->integer('value_id')->primary();
            $table->integer('attribute_id');
            $table->integer('entity_id');
            $table->string('value')->nullable();
        });
    }

    private function seedEavFixtureData(): void
    {
        DB::table('eav_entity_type')->insert([
            ['entity_type_id' => 1, 'entity_type_code' => 'catalog_product'],
            ['entity_type_id' => 2, 'entity_type_code' => 'catalog_category'],
            ['entity_type_id' => 3, 'entity_type_code' => 'customer'],
            ['entity_type_id' => 4, 'entity_type_code' => 'customer_address'],
        ]);

        DB::table('eav_attribute')->insert([
            ['attribute_id' => 10, 'entity_type_id' => 1, 'attribute_code' => 'name', 'backend_type' => 'varchar'],
            ['attribute_id' => 11, 'entity_type_id' => 1, 'attribute_code' => 'sku', 'backend_type' => 'static'],
            ['attribute_id' => 20, 'entity_type_id' => 2, 'attribute_code' => 'name', 'backend_type' => 'varchar'],
            ['attribute_id' => 30, 'entity_type_id' => 3, 'attribute_code' => 'firstname', 'backend_type' => 'varchar'],
            ['attribute_id' => 40, 'entity_type_id' => 4, 'attribute_code' => 'city', 'backend_type' => 'varchar'],
        ]);

        DB::table('catalog_product_entity')->insert([
            'entity_id' => 100,
            'sku' => 'sku-100',
        ]);

        DB::table('catalog_category_entity')->insert(['entity_id' => 200]);
        DB::table('customer_entity')->insert(['entity_id' => 300]);
        DB::table('customer_address_entity')->insert(['entity_id' => 400]);

        DB::table('catalog_product_entity_varchar')->insert([
            ['value_id' => 1000, 'attribute_id' => 10, 'store_id' => 0, 'entity_id' => 100, 'value' => 'Default Product'],
            ['value_id' => 1001, 'attribute_id' => 10, 'store_id' => 2, 'entity_id' => 100, 'value' => 'Scoped Product'],
        ]);

        DB::table('catalog_category_entity_varchar')->insert([
            'value_id' => 2000,
            'attribute_id' => 20,
            'store_id' => 0,
            'entity_id' => 200,
            'value' => 'Default Category',
        ]);

        DB::table('customer_entity_varchar')->insert([
            'value_id' => 3000,
            'attribute_id' => 30,
            'entity_id' => 300,
            'value' => 'Taylor',
        ]);

        DB::table('customer_address_entity_varchar')->insert([
            'value_id' => 4000,
            'attribute_id' => 40,
            'entity_id' => 400,
            'value' => 'Berlin',
        ]);
    }

    /**
     * @return list<string>
     */
    private function fixtureTables(): array
    {
        return [
            'catalog_product_entity_varchar',
            'catalog_category_entity_varchar',
            'customer_entity_varchar',
            'customer_address_entity_varchar',
            'catalog_product_entity',
            'catalog_category_entity',
            'customer_entity',
            'customer_address_entity',
            'eav_attribute',
            'eav_entity_type',
        ];
    }
}
