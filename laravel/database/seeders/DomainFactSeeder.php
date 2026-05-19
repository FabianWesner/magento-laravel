<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DomainFactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('domain_facts')
            ->whereIn('feature_key', ['catalog', 'category', 'product', 'search'])
            ->whereIn('entity_id', [1001, 1002, 1003, 2001, 2002, 3001, 3002, 3003, 4001, 4002])
            ->whereIn('store_id', [9001, 9002])
            ->delete();

        DB::table('domain_facts')->insert([
            [
                'feature_key' => 'catalog',
                'entity_id' => 1001,
                'store_id' => 9001,
                'store_view' => 'default',
                'payload' => json_encode([
                    'name' => 'Simple Shirt',
                    'sku' => 'simple-shirt',
                    'type' => 'simple',
                    'price' => 29.95,
                    'category' => 'Women',
                    'stock' => 24,
                    'url' => '/women/simple-shirt.html',
                    'short_description' => 'Lightweight cotton shirt for everyday catalog browsing.',
                    'swatch' => 'blue',
                    'rating' => 4.5,
                    'visibility' => 'Catalog, Search',
                ]),
            ],
            [
                'feature_key' => 'catalog',
                'entity_id' => 1002,
                'store_id' => 9001,
                'store_view' => 'default',
                'payload' => json_encode([
                    'name' => 'Configurable Hoodie',
                    'sku' => 'configurable-hoodie',
                    'type' => 'configurable',
                    'price' => 59.00,
                    'category' => 'Gear',
                    'stock' => 8,
                    'url' => '/gear/configurable-hoodie.html',
                    'short_description' => 'Size and color configurable hoodie used for filter and sort checks.',
                    'swatch' => 'black',
                    'rating' => 4.8,
                    'visibility' => 'Catalog, Search',
                ]),
            ],
            [
                'feature_key' => 'catalog',
                'entity_id' => 1003,
                'store_id' => 9002,
                'store_view' => 'de',
                'payload' => json_encode([
                    'name' => 'Einfaches Hemd',
                    'sku' => 'simple-shirt',
                    'type' => 'simple',
                    'price' => 31.95,
                    'category' => 'Damen',
                    'stock' => 18,
                    'url' => '/de/damen/einfaches-hemd.html',
                    'short_description' => 'Lokalisierte Store-View-Zeile fuer Katalogpruefungen.',
                    'swatch' => 'blau',
                    'rating' => 4.5,
                    'visibility' => 'Katalog, Suche',
                ]),
            ],
            [
                'feature_key' => 'category',
                'entity_id' => 2001,
                'store_id' => 9001,
                'store_view' => 'default',
                'payload' => json_encode([
                    'name' => 'Women',
                    'url_key' => 'women',
                    'is_active' => true,
                    'product_ids' => [3001],
                ]),
            ],
            [
                'feature_key' => 'category',
                'entity_id' => 2002,
                'store_id' => 9001,
                'store_view' => 'default',
                'payload' => json_encode([
                    'name' => 'Gear',
                    'url_key' => 'gear',
                    'is_active' => true,
                    'product_ids' => [3002],
                ]),
            ],
            [
                'feature_key' => 'product',
                'entity_id' => 3001,
                'store_id' => 9001,
                'store_view' => 'default',
                'payload' => json_encode([
                    'sku' => 'simple-shirt',
                    'name' => 'Simple Shirt',
                    'type' => 'simple',
                    'price' => 29.95,
                    'category_ids' => [2001],
                ]),
            ],
            [
                'feature_key' => 'product',
                'entity_id' => 3002,
                'store_id' => 9001,
                'store_view' => 'default',
                'payload' => json_encode([
                    'sku' => 'configurable-hoodie',
                    'name' => 'Configurable Hoodie',
                    'type' => 'configurable',
                    'price' => 59.00,
                    'category_ids' => [2002],
                ]),
            ],
            [
                'feature_key' => 'product',
                'entity_id' => 3003,
                'store_id' => 9002,
                'store_view' => 'de',
                'payload' => json_encode([
                    'sku' => 'simple-shirt',
                    'name' => 'Einfaches Hemd',
                    'type' => 'simple',
                    'price' => 31.95,
                    'category_ids' => [2001],
                ]),
            ],
            [
                'feature_key' => 'search',
                'entity_id' => 4001,
                'store_id' => 9001,
                'store_view' => 'default',
                'payload' => json_encode([
                    'query' => 'shirt',
                    'results_count' => 1,
                    'result_skus' => ['simple-shirt'],
                    'redirect' => null,
                ]),
            ],
            [
                'feature_key' => 'search',
                'entity_id' => 4002,
                'store_id' => 9002,
                'store_view' => 'de',
                'payload' => json_encode([
                    'query' => 'hemd',
                    'results_count' => 1,
                    'result_skus' => ['simple-shirt'],
                    'redirect' => null,
                ]),
            ],
        ]);
    }
}
