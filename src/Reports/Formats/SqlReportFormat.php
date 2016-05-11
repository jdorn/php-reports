<?php
namespace PhpReports\Formats;

use PhpReports\Report;
use flight\net\Request;

class SqlReportFormat extends Format implements FormatInterface
{
    /**
     * @{inheritDoc}
     */
    public static function display(Report &$report, Request &$request = null)
    {
        header("Content-type: text/plain");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo $report->renderReportPage('sql/report');
    }
}
