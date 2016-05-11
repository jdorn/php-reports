<?php
namespace PhpReports\Formats;

use PhpReports\Report;
use flight\net\Request;

class XmlReportFormat extends Format implements FormatInterface
{
    /**
     * @{inheritDoc}
     */
    public static function display(Report &$report, Request &$request = null)
    {
        header("Content-type: application/xml");
        header("Pragma: no-cache");
        header("Expires: 0");

        $datasets = [];
        $dataset_format = false;

        if (isset($_GET['datasets'])) {
            $dataset_format = true;
            $datasets = $_GET['datasets'];
            // If all the datasets should be included
            if ($datasets === 'all') {
                $datasets = array_keys($report->options['DataSets']);
            } elseif (!is_array($datasets)) {
                // If just a single dataset was specified, make it an array
                $datasets = explode(',', $datasets);
            }
        } else {
            $datasetIndex = 0;
            if (isset($_GET['dataset'])) {
                $datasetIndex = $_GET['dataset'];
            } elseif (isset($report->options['default_dataset'])) {
                $datasetIndex = $report->options['default_dataset'];
            }
            $datasetIndex = intval($datasetIndex);

            $datasets = [$datasetIndex];
        }

        echo $report->renderReportPage('xml/report', [
            'datasets' => $datasets,
            'dataset_format' => $dataset_format,
        ]);
    }
}
