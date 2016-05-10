<?php
namespace PhpReports\Formats;

class SqlReportFormat extends Format implements FormatInterface
{
    /**
     * @{inheritDoc}
     */
    public static function display(&$report, &$request)
    {
        header("Content-type: text/plain");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo $report->renderReportPage('sql/report');
    }
}
