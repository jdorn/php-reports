<?php
class JsonReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {		
		header("Content-type: application/json");
		header("Pragma: no-cache");
		header("Expires: 0");
		
		//run the report
		$report->run();
		
        if(!$report->options['DataSets']) return;

		$result = array();
		if(isset($_GET['datasets'])) {
			$datasets = $_GET['datasets'];
			// If all the datasets should be included
			if($datasets === 'all') {
				$datasets = array_keys($report->options['DataSets']);
			}
			// If just a single dataset was specified, make it an array
			else if(!is_array($datasets)) {
				$datasets = explode(',',$datasets);
			}
			
			foreach($datasets as $i) {
				$result[] = self::getDataSet($i, $report);
			}
		}
		else {
			$i=0;
			if(isset($_GET['dataset'])) $i = $_GET['dataset'];
			elseif(isset($report->options['default_dataset'])) $i = $report->options['default_dataset'];
			$i = intval($i);
			
			$dataset = self::getDataSet($i, $report);
			$result = $dataset['rows'];
		}

		if(defined('JSON_PRETTY_PRINT')) {
			echo json_encode($result,JSON_PRETTY_PRINT);
		}
		else {
			echo json_encode($result);
		}
	}
	
	public static function getDataSet($i, &$report) {
		$dataset = array();
		if(isset($report->options['DataSets'][$i]['title'])) {
			$dataset['title'] = $report->options['DataSets'][$i]['title'];
		}
		$dataset['rows'] = array();
		foreach($report->options['DataSets'][$i]['rows'] as $i=>$row) {
			$tmp = array();
			foreach($row['values'] as $key=>$value){
				$tmp[$value->key] = $value->getValue();
			}
			$dataset['rows'][] = $tmp;
		}
		return $dataset;
	}
}
