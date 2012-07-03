<?php
class IncludeHeader extends HeaderBase {
	static $validation = array(
		'report'=>array(
			'required'=>true,
			'type'=>'string'
		)
	);
	
	public static function init($params, &$report) {
		if($params['report'][0] === '/') {
			$report_path = substr($params['report'],1);
		}
		else {
			$report_path = dirname($report->report).'/'.$params['report'];
		}
		
		
		if(!file_exists(PhpReports::$config['reportDir'].'/'.$report_path)) {
			$possible_reports = glob(PhpReports::$config['reportDir'].'/'.$report_path.'.*');
			
			if($possible_reports) {
				$report_path = substr($possible_reports[0],strlen(PhpReports::$config['reportDir'].'/'));
			}
			else {
				throw new Exception("Unknown report in INCLUDE header '$report_path'");
			}
		}
		
		$included_report = new Report($report_path);
		
		//parse any exported headers from the included report
		foreach($included_report->exported_headers as $header) {
			$report->parseHeader($line['name'],$line['params']);
		}
		
		if(!isset($report->options['Includes'])) $report->options['Includes'] = array();
		
		$report->options['Includes'][] = $included_report;
	}
	
	public static function parseShortcut($value) {
		return array(
			'report'=>$value
		);
	}
}
