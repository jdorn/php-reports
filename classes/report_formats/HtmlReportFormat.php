<?php
class HtmlReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {
		//add csv download link to report
		$report->options['csv_link'] = PhpReports::$request->base.'/report/csv/?'.$_SERVER['QUERY_STRING'];
		
		$report->async = !isset($request->query['content_only']);
		if(isset($request->query['no_async'])) $report->async = false;
		
		try {
			$page_template = array(
				'content'=>$report->renderReportPage('html/table','html/report'),
				'has_charts'=>$report->options['has_charts']
			);
		}
		catch(Exception $e) {
			$page_template = array(
				'error'=>$e->getMessage(),
				'content'=>$report->options['Query_Formatted']
			);
		}
		
		$page_template['title'] = $report->options['Name'];

		if(isset($request->query['content_only'])) {
			PhpReports::renderPage($page_template,'html/content_only');
		}
		else {
			PhpReports::renderPage($page_template,'html/page');
		}
	}
}
