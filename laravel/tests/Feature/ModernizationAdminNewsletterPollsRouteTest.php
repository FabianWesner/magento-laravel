<?php

namespace Tests\Feature;

use App\Livewire\AdminNewsletterPollsWorkbench;
use Database\Seeders\DomainFactSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModernizationAdminNewsletterPollsRouteTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_newsletter_polls_route_renders_livewire_workbench(): void
    {
        $this->seed(DomainFactSeeder::class);

        $response = $this->get(route('modernization.admin.newsletter-polls'));

        $response
            ->assertOk()
            ->assertSee('Modernization Admin Newsletter Polls')
            ->assertSee(route('modernization.assets.admin-newsletter-polls'))
            ->assertSee('Admin newsletter and polls workbench')
            ->assertSee('Admin newsletter and polls diagnostics')
            ->assertSee('SF-016, AD-015, CJ-022')
            ->assertSee('maria.sommer@example.test')
            ->assertSee('Polls');
    }

    public function test_admin_newsletter_polls_asset_route_returns_css(): void
    {
        $response = $this->get(route('modernization.assets.admin-newsletter-polls'));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8');
    }

    public function test_admin_newsletter_polls_livewire_sections_filters_empty_and_denied_states(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(AdminNewsletterPollsWorkbench::class)
            ->assertSee('6 subscribers')
            ->assertSee('6 templates')
            ->assertSee('6 queue rows')
            ->assertSee('4 polls')
            ->assertSee('8 answers')
            ->assertSee('4 problems')
            ->assertSee('maria.sommer@example.test')
            ->assertSee('suppressed-newsletter@example.test')
            ->call('setSection', 'templates')
            ->assertSee('newsletter_subscription_success')
            ->assertSee('newsletter_campaign_de')
            ->call('setSection', 'queue')
            ->assertSee('newsletter-default-10601')
            ->assertSee('smtp_hard_bounce')
            ->set('status', 'failed')
            ->assertSee('suppressed-newsletter@example.test')
            ->assertDontSee('maria.sommer@example.test')
            ->set('status', '')
            ->call('setSection', 'polls')
            ->assertSee('Homepage Satisfaction')
            ->assertSee('Retired Holiday Survey')
            ->assertSee('Missing Answer Labels')
            ->assertSee('Startseite Bewertung')
            ->set('status', 'active')
            ->assertSee('Homepage Satisfaction')
            ->assertSee('Startseite Bewertung')
            ->assertDontSee('Retired Holiday Survey')
            ->set('status', '')
            ->set('storeView', 'de')
            ->assertSee('Startseite Bewertung')
            ->assertDontSee('Homepage Satisfaction')
            ->call('setSection', 'answers')
            ->assertSee('Sehr gut')
            ->assertSee('Verbesserbar')
            ->call('setSection', 'problems')
            ->assertSee('unterdrueckt-newsletter@example.test')
            ->set('query', 'not-present')
            ->assertSee('There are no problems facts matching this admin newsletter polls scope.')
            ->call('clearFilters')
            ->assertSee('maria.sommer@example.test')
            ->set('role', 'denied')
            ->assertSee('Permission denied for admin newsletter polls role `denied`.')
            ->assertDontSee('suppressed-newsletter@example.test');
    }

    public function test_admin_newsletter_polls_livewire_normalizes_invalid_public_state(): void
    {
        $this->seed(DomainFactSeeder::class);

        Livewire::test(AdminNewsletterPollsWorkbench::class)
            ->set('storeId', 'bad-store')
            ->set('storeView', 'bad-view')
            ->set('status', 'bad-status')
            ->set('type', 'bad-type')
            ->set('section', 'bad-section')
            ->set('role', 'bad-role')
            ->assertSet('storeId', '')
            ->assertSet('storeView', '')
            ->assertSet('status', '')
            ->assertSet('type', '')
            ->assertSet('section', 'subscribers')
            ->assertSet('role', 'catalog')
            ->assertSee('6 subscribers')
            ->assertDontSee('Store: bad-view');
    }
}
