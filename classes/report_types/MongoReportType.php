<?php
abstract class MongoReportType extends ReportTypeBase {
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
		
		//add a host macro
		if(isset($mongo_connections[$report->options['Database']]['webhost'])) $host = $mongo_connections[$report->options['Database']]['webhost'];
		else $host = $mongo_connections[$report->options['Database']]['host'];
		
		$report->macros['host'] = $host;
	}
	
	public static function openConnection(&$report) {
		
	}
	
	public static function closeConnection(&$report) {
		
	}
	
	public static function run(&$report) {		
		$eval = '';
		foreach($report->macros as $key=>$value) {
			$eval .= 'var '.$key.' = "'.addslashes($value).'";';
		}
		
		$mongo_connections = PhpReports::$config['mongo_connections'];
		$config = $mongo_connections[$report->options['Database']];
		
		$mongo_database = isset($report->options['Mongodatabase'])? $report->options['Mongodatabase'] : '';
		
		$command = 'mongo '.$config['host'].':'.$config['port'].'/'.$mongo_database.' --quiet --eval "'.addslashes($eval).'" '.PhpReports::$config['reportDir'].'/'.$report->report;
		
		$report->options['Query'] = '$ '.$command."\n\n".$report->raw_query;
		$report->options['Query_Formatted'] = '<div>
			<pre style="background-color: black; color: white; padding: 10px 5px;">$ '.$command.'</pre>'
			.PhpReports::$config['reportDir'].'/'.$report->report.
			'<pre style="border-left: 1px solid black; padding-left: 20px;">'.$report->raw_query.'</pre>
		</div>';
		
		$result = shell_exec($command);
		
		$rows = json_decode($result,true);
		
		if(!$rows) throw new Exception($result);
		
		return $rows;
	}
}
