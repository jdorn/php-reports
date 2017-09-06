<?php
abstract class XlsReportBase extends ReportFormatBase {
	private static function columnLetter($c){
		$c = intval($c);
		if ($c <= 0) return '';
		$letter = '';
		
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

		$i = 0;
		foreach($report->options['DataSets'] as $dataset) {
			$objPHPExcel->createSheet($i);
			self::addSheet($objPHPExcel,$dataset,$i);
			$i++;
		}
		
		// Set the active sheet to the first one
		$objPHPExcel->setActiveSheetIndex(0);
		
		return $objPHPExcel;
	}
	public static function addSheet($objPHPExcel,$dataset, $i) {
		$rows = array();
		$row = array();
		$cols = 0;
		$first_row = $dataset['rows'][0];
		foreach($first_row['values'] as $key=>$value){
			array_push($row, $value->key);
			$cols++;
		}
		array_push($rows, $row);
		$row = array();

		foreach($dataset['rows'] as $r) {
			foreach($r['values'] as $key=>$value){
				array_push($row, $value->getValue());
			}
			array_push($rows, $row);
			$row = array();
		}

		$objPHPExcel->setActiveSheetIndex($i)->fromArray($rows, NULL, 'A1');
		$objPHPExcel->getActiveSheet()->setAutoFilter('A1:'.self::columnLetter($cols).count($rows));
		for ($a = 1; $a <= $cols; $a++) {
			$objPHPExcel->getActiveSheet()->getColumnDimension(self::columnLetter($a))->setAutoSize(true);
		}

		if(isset($dataset['title'])) {
			// Some characters are not allowed in Excel sheet titles
			$title = preg_replace('#[\\/*[\]:?]#','',$dataset['title']);

			// Max title length is 31 characters
			$title = substr($title, 0, 31);

			$objPHPExcel->getActiveSheet()->setTitle($title);
		}

		return $objPHPExcel;
	}
}
