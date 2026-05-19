<?php

namespace App\Modernization\Fixtures;

class FixtureMediaReadiness
{
    /**
     * @return list<string>
     */
    public function requiredFixtureAreas(): array
    {
        return [
            'Product types',
            'Websites and stores',
            'Pricing and promotions',
            'Tax shipping payment',
            'Admin roles',
            'API users',
            'Reports',
            'Cron jobs',
            'CMS and media',
            'Failure and resilience',
        ];
    }

    /**
     * @return list<string>
     */
    public function requiredMediaAreas(): array
    {
        return [
            'product images',
            'category images',
            'CMS media',
            'downloadable files',
            'missing media reference',
            'image cache regeneration',
        ];
    }

    public function strictCoverageCommand(string $databaseDsn = 'mysql:host=<host>;dbname=<fixture_db>'): string
    {
        return "DB_DSN='{$databaseDsn}' DB_USER=<user> DB_PASS=<pass> php dev/modernization/fixture-coverage-report.php --format=markdown --fail-on-gaps";
    }

    /**
     * @param  array<string, list<string>>  $featureIdsByArea
     * @return array{status: string, missing_areas: list<string>, feature_id_count: int}
     */
    public function strictCoverageReport(array $featureIdsByArea): array
    {
        $missingAreas = [];
        $featureIds = [];

        foreach ($this->requiredFixtureAreas() as $area) {
            $areaFeatureIds = $featureIdsByArea[$area] ?? [];

            if ($areaFeatureIds === []) {
                $missingAreas[] = $area;
            }

            array_push($featureIds, ...$areaFeatureIds);
        }

        return [
            'status' => $missingAreas === [] ? 'No Gaps' : 'Gaps',
            'missing_areas' => $missingAreas,
            'feature_id_count' => count(array_unique($featureIds)),
        ];
    }

    /**
     * @param  array{
     *     source: string,
     *     fixture_id: string,
     *     sanitized_at: string,
     *     sensitive_data: array<string, string>,
     *     unsafe_samples: list<string>,
     *     media_reference_check: string
     * }  $proof
     */
    public function acceptsSanitizedProjectDataProof(array $proof): bool
    {
        if ($proof['source'] !== 'production-derived') {
            return false;
        }

        if ($proof['fixture_id'] === '' || $proof['sanitized_at'] === '') {
            return false;
        }

        if ($proof['sensitive_data'] === [] || $proof['unsafe_samples'] !== []) {
            return false;
        }

        foreach ($proof['sensitive_data'] as $replacement) {
            if ($replacement === '' || str_contains($replacement, '@example.com')) {
                return false;
            }
        }

        return $proof['media_reference_check'] === 'matched database records';
    }
}
