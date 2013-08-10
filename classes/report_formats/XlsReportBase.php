<?php
abstract class XlsReportBase extends ReportFormatBase {
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
	
	public static function getExcelRepresantation(&$report) {
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
		$objPHPExcel->getActiveSheet()->setAutoFilter('A1:'.self::columnLetter($cols).count($rows));
		for ($a = 1; $a <= $cols; $a++) {
			$objPHPExcel->getActiveSheet()->getColumnDimension(self::columnLetter($a))->setAutoSize(true);
		}
		
		return $objPHPExcel;
	}
}