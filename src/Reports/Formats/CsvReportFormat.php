<?php
namespace PhpReports\Formats;

use PhpReports\Report;
use flight\net\Request;

class CsvReportFormat extends Format implements FormatInterface
{
    /**
     * @{inheritDoc}
     */
    public static function display(Report &$report, Request &$request = null)
    {
        //always use cache for CSV reports
        $report->use_cache = true;

        $file_name = preg_replace(['/[\s]+/', '/[^0-9a-zA-Z\-_\.]/'], ['_', ''], $report->options['Name']);

        header("Content-type: application/csv");
        header("Content-Disposition: attachment; filename=" . $file_name . ".csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        $datasetIndex = 0;
        if (isset($_GET['dataset'])) {
            $datasetIndex = $_GET['dataset'];
        } elseif (isset($report->options['default_dataset'])) {
            $datasetIndex = $report->options['default_dataset'];
        }
        $datasetIndex = intval($datasetIndex);

        $data = $report->renderReportPage('csv/report', [
            'dataset' => $datasetIndex,
        ]);

        if (trim($data)) {
            echo $data;
        }
    }
}
