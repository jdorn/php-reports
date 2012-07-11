<?php
abstract class ReportFormatBase {
	abstract public static function display(&$report, &$request);
	
	public static function prepareReport($report) {
		$environment = null;
		if(isset($_REQUEST['environment'])) {
			$environment = $_REQUEST['environment'];
			
			//store this database selection in the session
			$_SESSION['environment'] = $environment;
		}
		elseif(isset($_SESSION['environment'])) {
			$environment = $_SESSION['environment'];
		}
		
		$macros = array();
		if(isset($_GET['macros'])) $macros = $_GET['macros'];

		$report = new Report($report,$macros,$environment);

		return $report;
	}
}
