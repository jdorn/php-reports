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
		
		$i=0;
		if(isset($_GET['dataset'])) $i = $_GET['dataset'];
		elseif(isset($report->options['default_dataset'])) $i = $report->options['default_dataset'];
		$i = intval($i);
		
		$data = $report->renderReportPage('csv/report',array(
			'dataset'=>$i
		));
		
		if(trim($data)) echo $data;
	}
}
