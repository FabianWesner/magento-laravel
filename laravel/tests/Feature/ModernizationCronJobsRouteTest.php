<?php

namespace Tests\Feature;

use App\Livewire\CronJobsWorkbench;
use App\Modernization\Cron\CronJobCatalog;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationCronJobsRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_cron_jobs_route_renders_livewire_workbench(): void
    {
        $response = $this->get(route('modernization.admin.cron-jobs'));

        $response
            ->assertOk()
            ->assertSee('Modernization Admin Cron Jobs')
            ->assertSee(route('modernization.assets.cron-jobs'))
            ->assertSee('Cron jobs workbench')
            ->assertSee('CJ-001 through CJ-025')
            ->assertSee('25 tracked jobs')
            ->assertSee('16 scheduled replacements')
            ->assertSee('6 config-driven')
            ->assertSee('3 bridge decisions')
            ->assertSee('9 attention rows')
            ->assertSee('CJ-001')
            ->assertSee('Scheduled Backup')
            ->assertSee('backup/observer::scheduledBackup');

        $this->get(route('modernization.assets.cron-jobs'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8');
    }

    public function test_cron_job_catalog_classifies_scheduler_rows_for_browser_diagnostics(): void
    {
        $catalog = $this->app->make(CronJobCatalog::class);
        $jobs = $catalog->all();

        $this->assertCount(25, $jobs);
        $this->assertSame([
            'tracked' => 25,
            'scheduled' => 16,
            'config_driven' => 6,
            'bridge' => 3,
            'attention' => 9,
        ], $catalog->summary($jobs));

        $blocked = $catalog->filtered(['status' => 'blocked']);
        $reports = $catalog->filtered(['domain' => 'reports']);
        $captcha = $catalog->filtered(['query' => 'captcha']);

        $this->assertSame(['CJ-014', 'CJ-021'], array_column($blocked, 'feature_id'));
        $this->assertCount(7, $reports);
        $this->assertSame(['CJ-023', 'CJ-024'], array_column($captcha, 'feature_id'));
    }

    public function test_admin_cron_jobs_livewire_filters_sections_empty_and_denied_states(): void
    {
        Livewire::test(CronJobsWorkbench::class)
            ->assertSee('25 tracked jobs')
            ->assertSee('Scheduled Backup')
            ->assertSee('Generate Sitemaps')
            ->call('setSection', 'config')
            ->assertSee('6 config-driven')
            ->assertSee('Currency Rate Update')
            ->assertSee('Generate Sitemaps')
            ->call('setSection', 'reports')
            ->assertSee('Aggregate Sales Orders')
            ->assertSee('Aggregate Tax Reports')
            ->call('setSection', 'problems')
            ->assertSee('9 attention rows')
            ->assertSee('Catalog rule application can change prices')
            ->assertSee('Price reindexing changes storefront price output')
            ->set('decision', 'bridge')
            ->assertSee('3 tracked jobs')
            ->assertSee('Scheduled Backup')
            ->assertSee('Paypal Fetch Reports')
            ->set('query', 'not-present')
            ->assertSee('There are no problems jobs matching this cron scope.')
            ->call('clearFilters')
            ->set('query', 'captcha')
            ->assertSee('2 tracked jobs')
            ->assertSee('Delete Old Captcha Attempts')
            ->assertSee('Delete Expired Captcha Images')
            ->call('clearFilters')
            ->set('role', 'denied')
            ->assertSee('Permission denied for cron role `denied`.')
            ->assertDontSee('Scheduled Backup');
    }

    public function test_admin_cron_jobs_livewire_normalizes_invalid_public_filter_state(): void
    {
        Livewire::test(CronJobsWorkbench::class)
            ->set('status', 'bad-status')
            ->set('decision', 'bad-decision')
            ->set('domain', 'bad-domain')
            ->set('section', 'bad-section')
            ->assertSet('status', '')
            ->assertSet('decision', '')
            ->assertSet('domain', '')
            ->assertSet('section', 'jobs')
            ->assertSee('25 tracked jobs');
    }
}
