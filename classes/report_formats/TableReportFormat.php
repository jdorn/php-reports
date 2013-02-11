<?php
class TableReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {
		
		$report->options['inline_email'] = true;
		$report->use_cache = true;
		
		try {
			$html = $report->renderReportPage('html/table');
			echo $html;
		}
		catch(Exception $e) {
			
		}
	}
}
