<?php
class RawReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {		
		header("Content-type: text/plain");
		header("Pragma: no-cache");
		header("Expires: 0");
		
		echo PhpReports::render('text/page',array(
			'content'=>$report->getRaw()
		));
	}
}
