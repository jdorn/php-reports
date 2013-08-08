<?php
class XlsReportFormat extends ReportFormatBase {
	private static function columnLetter($c){
		$c = intval($c);
		if ($c <= 0) return '';

		while($c != 0){
			$p = ($c - 1) % 26;
			$c = intval(($c - $p) / 26);
			$letter = chr(65 + $p) . $letter;
		}

		return $letter;
	}
	
	public static function display(&$report, &$request) {
		$alpha = array('A','B','C','D','E','F','G','H','I','J','K', 'L','M','N','O','P','Q','R','S','T','U','V','W','X ','Y','Z');
		
		// First let set up some headers
		$file_name = preg_replace(array('/[\s]+/','/[^0-9a-zA-Z\-_\.]/'),array('_',''),$report->options['Name']);

		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.$file_name.'.xls"');
		header('Pragma: no-cache');
		header('Expires: 0');

		//always use cache for Excel reports
		$report->use_cache = true;

		//run the report
		$report->run();

		if(!$report->options['Rows']) return;

		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		// Set document properties
		$objPHPExcel->getProperties()->setCreator("PHP-Reports")
									 ->setLastModifiedBy("PHP-Reports")
									 ->setTitle("")
									 ->setSubject("")
									 ->setDescription("");


		$rows = array();
		$row = array();
		$cols = 0;
		$first_row = $report->options['Rows'][0];
		foreach($first_row['values'] as $key=>$value){
			array_push($row, $value->key);
			$cols++;
		}
		array_push($rows, $row);
		$row = array();

		foreach($report->options['Rows'] as $r) {
			foreach($r['values'] as $key=>$value){
				array_push($row, $value->getValue());
			}
			array_push($rows, $row);
			$row = array();
		}

		$objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->setActiveSheetIndex(0)->fromArray($rows, NULL, 'A1');
		$objPHPExcel->getActiveSheet()->setAutoFilter('A1:'.$alpha[$cols-1].count($rows));
		for ($a = 1; $a <= $cols; $a++) {
			$objPHPExcel->getActiveSheet()->getColumnDimension(self::columnLetter($a))->setAutoSize(true);
		}

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save('php://output');
	}
}