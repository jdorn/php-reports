<?php
namespace PhpReports\Filters;

abstract class ReportFormatBase
{
    public static function prepareReport($report)
    {
        $environment = $_SESSION['environment'];

        $macros = [];

        if (isset($_GET['macros'])) {
            $macros = $_GET['macros'];
        }

        $report = new Report($report, $macros, $environment);

        return $report;
    }
}
