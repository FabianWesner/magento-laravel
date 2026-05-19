<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportFactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('report_facts')
            ->whereIn('report_key', ['sales', 'coupon', 'tax'])
            ->whereIn('bucket', ['2026-05-01', '2026-05-02'])
            ->whereIn('store_id', [9001, 9002])
            ->delete();

        DB::table('report_facts')->insert([
            [
                'report_key' => 'sales',
                'bucket' => '2026-05-01',
                'amount' => 100.00,
                'store_id' => 9001,
                'currency' => 'USD',
                'reported_at' => '2026-05-01',
            ],
            [
                'report_key' => 'sales',
                'bucket' => '2026-05-01',
                'amount' => 25.00,
                'store_id' => 9001,
                'currency' => 'USD',
                'reported_at' => '2026-05-01',
            ],
            [
                'report_key' => 'sales',
                'bucket' => '2026-05-02',
                'amount' => 55.00,
                'store_id' => 9002,
                'currency' => 'EUR',
                'reported_at' => '2026-05-02',
            ],
            [
                'report_key' => 'coupon',
                'bucket' => '2026-05-01',
                'amount' => 7.50,
                'store_id' => 9001,
                'currency' => 'USD',
                'reported_at' => '2026-05-01',
            ],
            [
                'report_key' => 'tax',
                'bucket' => '2026-05-01',
                'amount' => 8.25,
                'store_id' => 9001,
                'currency' => 'USD',
                'reported_at' => '2026-05-01',
            ],
        ]);
    }
}
