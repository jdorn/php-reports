<?php
class RawReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {		
		header("Content-type: text/plain");
		header("Pragma: no-cache");
		header("Expires: 0");
		
		PhpReports::renderPage(array(
			'content'=>$report->getRaw()
		),'text/page');
	}
}
