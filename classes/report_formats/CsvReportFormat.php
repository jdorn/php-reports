<?php
class CsvReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {
		//always use cache for CSV reports
		$report->use_cache = true;
		
		$file_name = preg_replace(array('/[\s]+/','/[^0-9a-zA-Z\-_\.]/'),array('_',''),$report->options['Name']);
		
		header("Content-type: application/csv");
		header("Content-Disposition: attachment; filename=".$file_name.".csv");
		header("Pragma: no-cache");
		header("Expires: 0");
		
		$data = $report->renderReportPage('csv/report');
		
		if(trim($data)) echo $data;
	}
}
