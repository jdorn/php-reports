<?php
class MysqlReportType extends ReportTypeBase {
	public static function init(&$report) {		
		$mysql_connections = PhpReports::$config['mysql_connections'];
	
		//if the database isn't set or doesn't exist, use the first defined one
		if(!$report->options['Database'] || !isset($mysql_connections[$report->options['Database']])) {
			$report->options['Database'] = current(array_keys($mysql_connections));
		}
		
		//add a host macro
		if(isset($mysql_connections[$report->options['Database']]['webhost'])) $host = $mysql_connections[$report->options['Database']]['webhost'];
		else $host = $mysql_connections[$report->options['Database']]['host'];
		
		$report->raw_query = preg_replace('/([^\{])(\{[a-zA-Z0-9_\-]+\})([^\}])/','$1{{$2}}$3',$report->raw_query);
		$report->macros['host'] = $host;
		
		//if there are any included reports, add the report sql to the top
		if(isset($report->options['Includes'])) {
			$included_sql = '';
			foreach($report->options['Includes'] as &$included_report) {
				$included_sql .= trim($included_report->raw_query);
			}
			
			$report->raw_query = $included_sql . "\n" . $report->raw_query;
		}
		
		//set up list of all available databases for displaying form for switching between them
		$report->options['Databases'] = array();
		foreach(array_keys($mysql_connections) as $name) {
			$report->options['Databases'][] = array(
				'selected'=>($report->options['Database'] === $name),
				'name'=>$name
			);
		}
	}
	
	public static function openConnection(&$report) {
		if(isset($report->conn)) return;
		
		$mysql_connections = PhpReports::$config['mysql_connections'];
		$config = $mysql_connections[$report->options['Database']];
		
		//the default is to use a user with read only privileges
		$username = $config['username'];
		$password = $config['password'];
		$host = $config['host'];
		
		//if the report requires read/write privileges
		if(isset($report->options['access']) && $report->options['access']==='rw') {
			if(isset($config['username_rw'])) $username = $config['username_rw'];
			if(isset($config['password_rw'])) $password = $config['password_rw'];
			if(isset($config['host_rw'])) $host = $config['host_rw'];
		}
		
		if(!($report->conn = mysql_connect($host, $username, $password))) {
			throw new Exception('Could not connect to Mysql: '.mysql_error());
		}
		if(!mysql_select_db($config['database'],$report->conn)) {
			throw new Exception('Could not select Mysql database: '.mysql_error($report->conn));
		}
	}
	
	public static function closeConnection(&$report) {
		if(!isset($report->conn)) return;
		mysql_close($report->conn);
		unset($report->conn);
	}
	
	public static function getVariableOptions($params, &$report) {
		$query = 'SELECT DISTINCT '.$params['column'].' FROM '.$params['table'];
		
		if(isset($params['where'])) {
			$query .= ' WHERE '.$params['where'];
		}
		
		$result = mysql_query($query, $report->conn);
		
		if(!$result) {
			throw new Exception("Unable to get variable options: ".mysql_error());
		}
		
		$options = array();
		
		if(isset($params['all'])) $options[] = 'ALL';
		
		while($row = mysql_fetch_assoc($result)) {
			$options[] = $row[$params['column']];
		}
		
		return $options;
	}
	
	public static function run(&$report) {
		$rows = array();
		
		$macros = $report->macros;
		foreach($macros as $key=>$value) {
			if(is_array($value)) {
				$first = true;
				foreach($value as $key2=>$value2) {
					$value[$key2] = array(
						'first'=>$first,
						'value'=>mysql_real_escape_string($value2)
					);
					$first = false;
				}
				$macros[$key] = $value;
			}
			
			if($value === 'ALL') $macros[$key.'_all'] = true;
		}
		
		//expand macros in query
		$m = new Mustache;
		
		$sql = $m->render($report->raw_query,$macros);
		
		$report->options['Query'] = $sql;
		
		$report->options['Query_Formatted'] = SqlFormatter::highlight($sql);
		
		//split queries and run each one, saving the last result		
		$queries = SqlFormatter::splitQuery($sql);
		
		foreach($queries as $query) {
			//skip empty queries
			$query = trim($query);
			if(!$query) continue;
			
			$result = mysql_query($query,$report->conn);
			if(!$result) {
				throw new Exception("Query failed: ".mysql_error($report->conn));
			}
		}
		
		while($row = mysql_fetch_assoc($result)) {
			$rows[] = $row;
		}
		
		return $rows;
	}
}
