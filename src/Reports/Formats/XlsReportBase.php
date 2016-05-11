<?php
namespace PhpReports\Formats;

use PHPExcel;
use PhpReports\Report;

abstract class XlsReportBase extends Format implements FormatInterface
{
    /**
     * @param int $columnLetter
     * @return string $letter
     */
    private static function columnLetter($columnLetter)
    {
        $columnLetter = intval($columnLetter);
        if ($columnLetter <= 0) {
            return '';
        }
        $letter = '';

        while ($columnLetter != 0) {
            $page = ($columnLetter - 1) % 26;
            $columnLetter = intval(($columnLetter - $page) / 26);
            $letter = chr(65 + $page) . $letter;
        }

        return $letter;
    }

    public static function getExcelRepresantation(Report &$report)
    {
        // Create new PHPExcel object
        $objPHPExcel = new PHPExcel();

        // Set document properties
        $objPHPExcel->getProperties()->setCreator("PHP-Reports")
                                     ->setLastModifiedBy("PHP-Reports")
                                     ->setTitle("")
                                     ->setSubject("")
                                     ->setDescription("");

        foreach ($report->options['DataSets'] as $datasetIndex => $dataset) {
            $objPHPExcel->createSheet($datasetIndex);
            self::addSheet($objPHPExcel, $dataset, $datasetIndex);
        }

        // Set the active sheet to the first one
        $objPHPExcel->setActiveSheetIndex(0);

        return $objPHPExcel;
    }

    public static function addSheet($objPHPExcel, $dataset, $i)
    {
        $rows = [];
        $row = [];
        $cols = 0;
        $first_row = $dataset['rows'][0];
        foreach ($first_row['values'] as $key => $value) {
            array_push($row, $value->key);
            $cols++;
        }
        array_push($rows, $row);
        $row = [];

        foreach ($dataset['rows'] as $r) {
            foreach ($r['values'] as $key => $value) {
                array_push($row, $value->getValue());
            }
            array_push($rows, $row);
            $row = [];
        }

        $objPHPExcel->setActiveSheetIndex($i)->fromArray($rows, null, 'A1');
        $objPHPExcel->getActiveSheet()->setAutoFilter('A1:'.self::columnLetter($cols).count($rows));
        for ($columnLeter = 1; $columnLeter <= $cols; $columnLeter++) {
            $objPHPExcel->getActiveSheet()->getColumnDimension(self::columnLetter($columnLeter))->setAutoSize(true);
        }

        if (isset($dataset['title'])) {
            $objPHPExcel->getActiveSheet()->setTitle($dataset['title']);
        }

        return $objPHPExcel;
    }
}
