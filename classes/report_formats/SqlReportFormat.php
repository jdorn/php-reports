<?php
class SqlReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {
		$page_template = array(
			'content'=>$report->renderReportPage('sql/report','sql/page')
		);
		
		header("Content-type: text/plain");
		header("Pragma: no-cache");
		header("Expires: 0");
		
		echo PhpReports::render('sql/page',$page_template);
	}
}
