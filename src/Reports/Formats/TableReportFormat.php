<?php
namespace PhpReports\Formats;

class TableReportFormat extends Format implements FormatInterface
{
    /**
     * @{inheritDoc}
     */
    public static function display(&$report, &$request)
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
