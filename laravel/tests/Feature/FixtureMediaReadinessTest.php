<?php

namespace Tests\Feature;

use App\Modernization\Fixtures\FixtureMediaReadiness;
use Tests\TestCase;

class FixtureMediaReadinessTest extends TestCase
{
    public function test_strict_fixture_coverage_report_requires_no_gaps_before_final_acceptance(): void
    {
        $readiness = $this->app->make(FixtureMediaReadiness::class);

        $report = $readiness->strictCoverageReport([
            'Product types' => ['SF-005', 'CB-001'],
            'Websites and stores' => ['SF-001', 'CB-014'],
            'Pricing and promotions' => ['CB-002', 'CB-004'],
            'Tax shipping payment' => ['CB-003', 'CB-005', 'CB-006'],
            'Admin roles' => ['AD-001', 'AD-002'],
            'API users' => ['API-001'],
            'Reports' => ['AD-013'],
            'Cron jobs' => ['CJ-001'],
            'CMS and media' => ['SF-002', 'AD-009'],
            'Failure and resilience' => ['SF-008', 'AD-011'],
        ]);

        $this->assertStringContainsString('fixture-coverage-report.php', $readiness->strictCoverageCommand());
        $this->assertStringContainsString('--fail-on-gaps', $readiness->strictCoverageCommand());
        $this->assertSame('No Gaps', $report['status'], 'strict fixture coverage report with no gaps');
        $this->assertSame([], $report['missing_areas']);
        $this->assertGreaterThanOrEqual(10, $report['feature_id_count']);
    }

    public function test_sanitized_project_data_proof_requires_sensitive_data_replacement_and_media_reference_match(): void
    {
        $readiness = $this->app->make(FixtureMediaReadiness::class);

        $proof = [
            'source' => 'production-derived',
            'fixture_id' => 'sanitized-project-fixture-2026-05',
            'sanitized_at' => '2026-05-19T08:55:00+02:00',
            'sensitive_data' => [
                'customer.email' => 'customer-1001@fixture.invalid',
                'admin.password_hash' => 'rotated-fixture-hash',
                'api.oauth_token' => 'fixture-token-redacted',
            ],
            'unsafe_samples' => [],
            'media_reference_check' => 'matched database records',
        ];

        $this->assertContains('CMS media', $readiness->requiredMediaAreas());
        $this->assertContains('downloadable files', $readiness->requiredMediaAreas());
        $this->assertTrue($readiness->acceptsSanitizedProjectDataProof($proof), 'sanitized project data proof covers Sensitive Data sanitization for production-derived fixtures');
    }

    public function test_sanitized_project_data_proof_rejects_unsafe_samples(): void
    {
        $readiness = $this->app->make(FixtureMediaReadiness::class);

        $this->assertFalse($readiness->acceptsSanitizedProjectDataProof([
            'source' => 'production-derived',
            'fixture_id' => 'sanitized-project-fixture-2026-05',
            'sanitized_at' => '2026-05-19T08:55:00+02:00',
            'sensitive_data' => ['customer.email' => 'customer-1001@fixture.invalid'],
            'unsafe_samples' => ['real customer email retained'],
            'media_reference_check' => 'matched database records',
        ]));
    }
}
