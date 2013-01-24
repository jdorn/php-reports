<?php
abstract class ReportFormatBase {
	abstract public static function display(&$report, &$request);
	
	public static function prepareReport($report) {
		$environment = $_SESSION['environment'];

		$macros = array();
		if(isset($_GET['macros'])) $macros = $_GET['macros'];

		$report = new Report($report,$macros,$environment);

		return $report;
	}
}
