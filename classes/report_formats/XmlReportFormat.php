<?php
class XmlReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {
		header("Content-type: application/xml");
		header("Pragma: no-cache");
		header("Expires: 0");

		echo $report->renderReportPage('xml/report');
	}
}
