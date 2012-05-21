<?php
class SqlReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {
		$page_template = array(
			'content'=>$report->renderReportPage('sql/report','sql/page')
		);
		
		header("Content-type: text/plain");
		header("Pragma: no-cache");
		header("Expires: 0");
		
		PhpReports::renderPage($page_template,'sql/page');
	}
}
