<?php
class ChartReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {
		if(!$report->options['has_charts']) return;
		
		//always use cache for chart reports
		$report->use_cache = true;
		
		$result = $report->renderReportPage('html/chart_report');
		
		echo $result;
	}
}
