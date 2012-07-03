<?php
class HtmlReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {
		//add csv download link to report
		$report->options['csv_link'] = PhpReports::$request->base.'/report/csv/?'.$_SERVER['QUERY_STRING'];
		
		$report->async = !isset($request->query['content_only']);
		if(isset($request->query['no_async'])) $report->async = false;
		
		if(isset($request->query['content_only'])) {
			try {
				$content = $report->renderReportPage('html/table','html/content_only');
				echo $content;
			}
			catch(Exception $e) {
				$page_template = array(
					'error'=>$e->getMessage(),
					'content'=>$report->options['Query_Formatted'],
				);
				echo PhpReports::render('html/content_only',$page_template);
			}
		}
		else {
			try {
				$page_template = array(
					'title'=>$report->options['Name'],
					'breadcrumb'=>array('Report List'=>'',$report->options['Name']=>true),
					'has_charts'=>$report->options['has_charts'],
					'content'=>$report->renderReportPage('html/table','html/report'),
				);
			}
			catch(Exception $e) {
				$page_template = array(
					'title'=>$report->options['Name'],
					'breadcrumb'=>array('Report List'=>'',$report->options['Name']=>true),
					'error'=>$e->getMessage(),
					'content'=>$report->options['Query_Formatted'],
				);
			}
			echo PhpReports::render('html/page',$page_template);
		}
	}
}
