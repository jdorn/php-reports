<?php
namespace PhpReports\Formats;

use PhpReports\Report;
use flight\net\Request;

class TextReportFormat extends Format implements FormatInterface
{
    /**
     * @{inheritDoc}
     */
    public static function display(Report &$report, Request &$request = null)
    {
        header("Content-type: text/plain");
        header("Pragma: no-cache");
        header("Expires: 0");

        $report->use_cache = true;

        //run the report
        $report->run();

        if (!$report->options['DataSets']) {
            return;
        }

        foreach ($report->options['DataSets'] as $i => $dataset) {
            if (isset($dataset['title'])) {
                echo $dataset['title']."\n";
            }

            TextReportFormat::displayDataSet($dataset);

            // If this isn't the last dataset, add some spacing
            if ($i < count($report->options['DataSets'])-1) {
                echo "\n\n";
            }
        }
    }

    protected static function displayDataSet($dataset)
    {
        /**
         * This code taken from Stack Overflow answer by ehudokai
         * http://stackoverflow.com/a/4597190
         */

        //first get your sizes
        $sizes = [];
        $first_row = $dataset['rows'][0];
        foreach ($first_row['values'] as $key => $value) {
            $key = $value->key;
            $value = $value->getValue();

            //initialize to the size of the column name
            $sizes[$key] = strlen($key);
        }

        foreach ($dataset['rows'] as $row) {
            foreach ($row['values'] as $key => $value) {
                $key = $value->key;
                $value = $value->getValue();

                $length = strlen($value);
                // get largest result size
                if ($length > $sizes[$key]) {
                    $sizes[$key] = $length;
                }
            }
        }

        //top of output
        foreach ($sizes as $length) {
            echo "+" .str_pad("", $length + 2, "-");
        }
        echo "+\n";

        // column names
        foreach ($first_row['values'] as $key => $value) {
            $key = $value->key;
            $value = $value->getValue();

            echo "| ";
            echo str_pad($key, $sizes[$key]+1);
        }
        echo "|\n";

        //line under column names
        foreach ($sizes as $length) {
            echo "+" . str_pad("", $length + 2, "-");
        }
        echo "+\n";

        //output data
        foreach ($dataset['rows'] as $row) {
            foreach ($row['values'] as $key => $value) {
                $key = $value->key;
                $value = $value->getValue();

                echo "| ";
                echo str_pad($value, $sizes[$key]+1);
            }
            echo "|\n";
        }

        //bottom of output
        foreach ($sizes as $length) {
            echo "+" . str_pad("", $length + 2, "-");
        }
        echo "+\n";
    }
}
