<?php
abstract class PhpReportType extends ReportTypeBase {
	public static function init(&$report) {
		
	}
	
	public static function openConnection(&$report) {
		
	}
	
	public static function closeConnection(&$report) {
		
	}
	
	public static function run(&$report) {		
		extract($report->macros);
		
		ob_start();
		require(PhpReports::$config['reportDir'].'/'.$report->report);
		$result = ob_get_contents();
		ob_end_clean();
		
		return json_decode($result,true);
	}
}
