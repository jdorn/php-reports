<?php
class TextReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {
		$page_template = array(
			'content'=>$report->renderReportPage('text/report','text/page')
		);
		
		header("Content-type: text/plain");
		header("Pragma: no-cache");
		header("Expires: 0");

		PhpReports::renderPage($page_template,'text/page');
	}
}
