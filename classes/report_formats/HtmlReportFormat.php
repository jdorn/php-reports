<?php
class HtmlReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {
		//determine if this is an asyncronous report or not		
		$report->async = !isset($request->query['content_only']);
		if(isset($request->query['no_async'])) $report->async = false;
		
		//if we're only getting the report content
		if(isset($request->query['content_only'])) {
			$template = 'html/content_only';
		}
		else {
			$template = 'html/report';
		}
		
		echo $report->renderReportPage($template);
	}
}
