<?php

namespace App\Jobs\Modernization\Reports;

use App\Modernization\Reports\ReportCatalog;
use App\Modernization\Reports\ReportQuery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AggregateReportTables implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $reportKeys
     */
    public function __construct(public readonly array $reportKeys) {}

    /**
     * Execute the job.
     */
    public function handle(ReportCatalog $catalog, ReportQuery $query): void
    {
        foreach ($this->reportKeys as $reportKey) {
            $result = $query->run($catalog->get($reportKey));

            Log::info('Report aggregation completed', [
                'report aggregation' => $reportKey,
                'report table' => $result->definition->table,
                'rows' => $result->count,
                'total' => $result->total,
            ]);
        }
    }
}
