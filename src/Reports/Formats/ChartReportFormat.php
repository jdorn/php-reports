<?php
namespace PhpReports\Formats;

use PhpReports\Report;
use flight\net\Request;

class ChartReportFormat extends Format implements FormatInterface
{
    /**
     * @{inheritDoc}
     */
    public static function display(Report &$report, Request &$request = null)
    {
        if (!$report->options['has_charts']) {
            return;
        }

        //always use cache for chart reports
        $report->use_cache = true;

        $result = $report->renderReportPage('html/chart_report');

        echo $result;
    }
}
