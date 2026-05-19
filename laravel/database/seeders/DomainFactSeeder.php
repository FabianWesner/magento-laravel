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
            ->whereIn('feature_key', ['catalog', 'category', 'product', 'search', 'customer', 'customer_address'])
            ->whereIn('entity_id', [1001, 1002, 1003, 2001, 2002, 3001, 3002, 3003, 4001, 4002, 5001, 5002, 6001, 6002])
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
            [
                'feature_key' => 'customer',
                'entity_id' => 5001,
                'store_id' => 9001,
                'store_view' => 'default',
                'payload' => json_encode([
                    'customer_id' => 5001,
                    'email' => 'maria.sommer@example.test',
                    'firstname' => 'Maria',
                    'lastname' => 'Sommer',
                    'full_name' => 'Maria Sommer',
                    'group' => 'General',
                    'is_active' => true,
                    'created_at' => '2026-01-15T10:30:00+00:00',
                    'last_login_at' => '2026-05-10T08:15:00+00:00',
                    'orders_count' => 3,
                    'lifetime_value' => 184.70,
                    'newsletter_subscribed' => true,
                    'default_billing_address_id' => 6001,
                    'default_shipping_address_id' => 6001,
                ]),
            ],
            [
                'feature_key' => 'customer',
                'entity_id' => 5002,
                'store_id' => 9002,
                'store_view' => 'de',
                'payload' => json_encode([
                    'customer_id' => 5002,
                    'email' => 'lena.keller@example.test',
                    'firstname' => 'Lena',
                    'lastname' => 'Keller',
                    'full_name' => 'Lena Keller',
                    'group' => 'Retail Kunde',
                    'is_active' => true,
                    'created_at' => '2026-02-20T11:45:00+00:00',
                    'last_login_at' => '2026-05-12T07:20:00+00:00',
                    'orders_count' => 2,
                    'lifetime_value' => 129.90,
                    'newsletter_subscribed' => false,
                    'default_billing_address_id' => 6002,
                    'default_shipping_address_id' => 6002,
                ]),
            ],
            [
                'feature_key' => 'customer_address',
                'entity_id' => 6001,
                'store_id' => 9001,
                'store_view' => 'default',
                'payload' => json_encode([
                    'address_id' => 6001,
                    'customer_id' => 5001,
                    'address_type' => 'billing_shipping',
                    'firstname' => 'Maria',
                    'lastname' => 'Sommer',
                    'company' => 'Example Retail LLC',
                    'street' => ['101 Market Street', 'Suite 400'],
                    'city' => 'Portland',
                    'region' => 'Oregon',
                    'postcode' => '97204',
                    'country_id' => 'US',
                    'telephone' => '+1-503-555-0198',
                    'is_default_billing' => true,
                    'is_default_shipping' => true,
                    'formatted_lines' => [
                        'Maria Sommer',
                        'Example Retail LLC',
                        '101 Market Street, Suite 400',
                        'Portland, Oregon 97204',
                        'US',
                    ],
                ]),
            ],
            [
                'feature_key' => 'customer_address',
                'entity_id' => 6002,
                'store_id' => 9002,
                'store_view' => 'de',
                'payload' => json_encode([
                    'address_id' => 6002,
                    'customer_id' => 5002,
                    'address_type' => 'billing_shipping',
                    'firstname' => 'Lena',
                    'lastname' => 'Keller',
                    'company' => 'Beispiel Handel GmbH',
                    'street' => ['Friedrichstrasse 40'],
                    'city' => 'Berlin',
                    'region' => 'Berlin',
                    'postcode' => '10117',
                    'country_id' => 'DE',
                    'telephone' => '+49-30-5550198',
                    'is_default_billing' => true,
                    'is_default_shipping' => true,
                    'formatted_lines' => [
                        'Lena Keller',
                        'Beispiel Handel GmbH',
                        'Friedrichstrasse 40',
                        '10117 Berlin',
                        'DE',
                    ],
                ]),
            ],
        ]);
    }
}
