<?php
namespace PhpReports\Formats;

use PHPExcel_IOFactory;

class XlsReportFormat extends XlsReportBase implements FormatInterface
{
    /**
     * @{inheritDoc}
     */
    public static function display(&$report, &$request)
    {
        // First let set up some headers
        $file_name = preg_replace(['/[\s]+/', '/[^0-9a-zA-Z\-_\.]/'], ['_', ''], $report->options['Name']);

        //always use cache for Excel reports
        $report->use_cache = true;

        //run the report
        $report->run();

        if (!$report->options['DataSets']) {
            return;
        }

        $objPHPExcel = parent::getExcelRepresantation($report);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$file_name.'.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $objWriter->save('php://output');
    }
}
