<?php
class IncludeHeader extends HeaderBase {
	public static function parse($key, $value, &$report) {
		if($value[0] === '/') {
			$report_path = substr($value,1);
		}
		else {
			$report_path = dirname($report->report).'/'.$value;
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
		
		/*
		//This is for importing the included report's headers
		//Disabled for now, since it is causing problems with inheritance
		foreach($included_report->header_lines as $line) {
			print_r($line);
			$report->parseHeader($line['name'],$line['value']);
		}
		*/
		
		if(!isset($report->options['Includes'])) $report->options['Includes'] = array();
		
		$report->options['Includes'][] = $included_report;
	}
	
	public static function beforeRender(&$report) {
		
	}
	
	public static function afterParse(&$report) {
		
	}
}
