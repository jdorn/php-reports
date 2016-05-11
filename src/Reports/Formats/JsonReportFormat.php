<?php
namespace PhpReports\Formats;

use PhpReports\Report;
use flight\net\Request;

class JsonReportFormat extends ReportFormatBase
{
    /**
     * @{inheritDoc}
     */
    public static function display(Report &$report)
    {
        header("Content-type: application/json");
        header("Pragma: no-cache");
        header("Expires: 0");

        //run the report
        $report->run();

        if (!$report->options['DataSets']) {
            return;
        }

        $result = [];
        if (isset($_GET['datasets'])) {
            $datasets = $_GET['datasets'];
            // If all the datasets should be included
            if ($datasets === 'all') {
                $datasets = array_keys($report->options['DataSets']);
            } elseif (!is_array($datasets)) {
                // If just a single dataset was specified, make it an array
                $datasets = explode(',', $datasets);
            }

            foreach ($datasets as $datasetIndex) {
                $result[] = self::getDataSet($datasetIndex, $report);
            }
        } else {
            $datasetIndex = 0;
            if (isset($_GET['dataset'])) {
                $datasetIndex = $_GET['dataset'];
            } elseif (isset($report->options['default_dataset'])) {
                $datasetIndex = $report->options['default_dataset'];
            }
            $datasetIndex = intval($datasetIndex);

            $dataset = self::getDataSet($datasetIndex, $report);
            $result = $dataset['rows'];
        }

        if (defined('JSON_PRETTY_PRINT')) {
            echo json_encode($result, JSON_PRETTY_PRINT);
        } else {
            echo json_encode($result);
        }
    }

    public static function getDataSet($datasetIndex, &$report)
    {
        $dataset = [];
        foreach ($report->options['DataSets'][$datasetIndex] as $k => $v) {
            $dataset[$k] = $v;
        }

        $rows = [];
        foreach ($dataset['rows'] as $datasetIndex => $row) {
            $tmp = [];
            foreach ($row['values'] as $value) {
                $tmp[$value->key] = $value->getValue();
            }
            $rows[] = $tmp;
        }
        $dataset['rows'] = $rows;

        return $dataset;
    }
}
