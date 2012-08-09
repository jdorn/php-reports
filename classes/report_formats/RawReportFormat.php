<?php
class RawReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {		
		header("Content-type: text/plain");
		header("Pragma: no-cache");
		header("Expires: 0");
		
		echo $report;
	}
	
	//no need to instantiate a report object, just return the source
	public static function prepareReport($report) {
		$contents = Report::getReportFileContents($report);
		
		return $contents;
	}
}
