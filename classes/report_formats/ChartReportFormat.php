<?php
class ChartReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {
		if(!$report->options['has_charts']) return;
		
		//always use cache for chart reports
		$report->use_cache = true;
		
		try {
			$page_template = array(
				'content'=>$report->renderReportContent('html/chart_report')
			);
		}
		catch(Exception $e) {
			$page_template = array(
				'error'=>$e->getMessage(),
				'content'=>$report->options['Query_Formatted'],
			);
		}	
		
		echo PhpReports::render('html/chart_page',$page_template);
		exit;
	}
}
