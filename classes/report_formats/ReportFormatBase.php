<?php
abstract class ReportFormatBase {
	abstract public static function display(&$report, &$request);
	
	public static function prepareReport($report) {
		$database = null;
		if(isset($_REQUEST['database'])) {
			$database = $_REQUEST['database'];
			
			//store this database selection in the session
			$_SESSION['database'] = $database;
		}
		elseif(isset($_SESSION['database'])) {
			$database = $_SESSION['database'];
		}
		
		$macros = array();
		if(isset($_GET['macros'])) $macros = $_GET['macros'];

		$report = new Report($report,$macros,$database);

		return $report;
	}
}
