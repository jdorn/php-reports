<?php
class JsonReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {		
		header("Content-type: application/json");
		header("Pragma: no-cache");
		header("Expires: 0");

		echo $report->renderReportPage('json/report');
	}
}
