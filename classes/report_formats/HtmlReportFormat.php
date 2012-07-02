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
				'has_charts'=>$report->options['has_charts'],
			);
		}
		catch(Exception $e) {
			$page_template = array(
				'error'=>$e->getMessage(),
				'content'=>$report->options['Query_Formatted'],
			);
		}
		
		$page_template['title'] = $report->options['Name'];
		$page_template['breadcrumb'] = array('Report List'=>'',$report->options['Name']=>true);

		if(isset($request->query['content_only'])) {		
			echo PhpReports::render('html/content_only',$page_template);
			exit;
		}
		else {
			echo PhpReports::render('html/page',$page_template);
		}
	}
}
