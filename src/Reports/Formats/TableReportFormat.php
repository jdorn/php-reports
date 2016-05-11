<?php
namespace PhpReports\Formats;

use PhpReports\Report;
use flight\net\Request;

class TableReportFormat extends Format implements FormatInterface
{
    /**
     * @{inheritDoc}
     */
    public static function display(Report &$report, Request &$request = null)
    {
        $report->options['inline_email'] = true;
        $report->use_cache = true;

        try {
            $html = $report->renderReportPage('html/table');
            echo $html;
        } catch (\Exception $e) {
        }
    }
}
