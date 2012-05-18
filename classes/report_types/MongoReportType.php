<?php
abstract class MysqlReportType extends ReportTypeBase {
	public static function init(&$report) {
		$mongo_connections = PhpReports::$config['mongo_connections'];
	
		//if the database isn't set or doesn't exist, use the first defined one
		if(!$report->options['Database'] || !isset($mongo_connections[$report->options['Database']])) {
			$report->options['Database'] = current(array_keys($mongo_connections));
		}
		
		//set up list of all available databases for displaying form for switching between them
		$report->options['Databases'] = array();
		foreach(array_keys($mongo_connections) as $name) {
			$report->options['Databases'][] = array(
				'selected'=>($report->options['Database'] == $name),
				'name'=>$name
			);
		}
	}
	
	public static function openConnection(&$report) {
		
	}
	
	public static function closeConnection(&$report) {
		
	}
	
	public static function run(&$report) {		
		$eval = '';
		foreach($this->macros as $key=>$value) {
			$eval .= 'var '.$key.' = "'.addslashes($value).'";';
		}
		$command = 'mongo '.$mongo_connections[$config]['host'].':'.$mongo_connections[$config]['port'].'/'.$options['Database'].' --quiet --eval "'.addslashes($eval).'" '.$report;
		echo $command;
		
		$options['Query'] = $command;
		
		throw new Exception("Not implemented");
	}
}
