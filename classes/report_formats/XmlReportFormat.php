<?php
class XmlReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {		
		$page_template = array(
			'content'=>$report->renderReportPage('xml/rows','xml/report')
		);
		
		header("Content-type: application/xml");
		header("Pragma: no-cache");
		header("Expires: 0");

		echo PhpReports::render('xml/page',$page_template);
	}
}
