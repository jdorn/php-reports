<?php
class DebugReportFormat extends ReportFormatBase {
	public static function display(&$report, &$request) {
		header("Content-type: text/plain");
		header("Pragma: no-cache");
		header("Expires: 0");
		
		$content = "****************** Raw Report File ******************\n\n".$report->getRaw()."\n\n\n";
		$content .= "****************** Macros ******************\n\n".print_r($report->macros,true)."\n\n\n";
		$content .= "****************** All Report Options ******************\n\n".print_r($report->options,true)."\n\n\n";
		
		if($report->is_ready) {
			$report->run();
		
			$content .= "****************** Generated Query ******************\n\n".print_r($report->options['Query'],true)."\n\n\n";
			
			$content .= "****************** Report Rows ******************\n\n".print_r($report->options['DataSets'],true)."\n\n\n";
		}
		
		echo $content;
	}
}
