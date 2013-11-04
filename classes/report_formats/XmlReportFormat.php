<?php
class XmlReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {
		header("Content-type: application/xml");
		header("Pragma: no-cache");
		header("Expires: 0");

		$datasets = array();
		$dataset_format = false;
		
		if(isset($_GET['datasets'])) {
			$dataset_format = true;
			$datasets = $_GET['datasets'];
			// If all the datasets should be included
			if($datasets === 'all') {
				$datasets = array_keys($report->options['DataSets']);
			}
			// If just a single dataset was specified, make it an array
			else if(!is_array($datasets)) {
				$datasets = explode(',',$datasets);
			}
		}
		else {
			$i=0;
			if(isset($_GET['dataset'])) $i = $_GET['dataset'];
			elseif(isset($report->options['default_dataset'])) $i = $report->options['default_dataset'];
			$i = intval($i);
			
			$datasets = array($i);
		}

		echo $report->renderReportPage('xml/report',array(
			'datasets'=>$datasets,
			'dataset_format'=>$dataset_format
		));
	}
}
