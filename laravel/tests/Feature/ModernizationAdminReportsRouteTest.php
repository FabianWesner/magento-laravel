<?php

namespace Tests\Feature;

use App\Livewire\AdminReportsWorkbench;
use Database\Seeders\ReportFactSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationAdminReportsRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_reports_route_renders_livewire_workbench(): void
    {
        $this->seed(ReportFactSeeder::class);

        $response = $this->get(route('modernization.admin.reports'));

        $response
            ->assertOk()
            ->assertSee('Modernization Admin Reports')
            ->assertSee(route('modernization.assets.admin-reports'))
            ->assertSee('Reports workbench')
            ->assertSee('Admin reports')
            ->assertSee('Sales report')
            ->assertSee('AD-014, CJ-007')
            ->assertSee('Total')
            ->assertSee('Rows')
            ->assertSee('Mixed');
    }

    public function test_admin_reports_livewire_filters_exports_and_denies_permissions(): void
    {
        $this->seed(ReportFactSeeder::class);

        Livewire::test(AdminReportsWorkbench::class)
            ->assertSet('reportKey', 'sales')
            ->assertSee('Sales report')
            ->assertSee('180.00')
            ->assertSee('Mixed')
            ->set('storeId', '9001')
            ->set('currency', 'USD')
            ->assertSee('125.00')
            ->assertSee('USD')
            ->set('reportKey', 'coupon')
            ->assertSee('Coupon report')
            ->assertSee('7.50')
            ->call('toggleCsv')
            ->assertSee('bucket,currency,total,count,average')
            ->set('reportKey', 'low_stock')
            ->assertSee('Empty report state for Low stock report')
            ->set('role', 'denied')
            ->assertSee('Permission denied for report role `denied`.');
    }
}
