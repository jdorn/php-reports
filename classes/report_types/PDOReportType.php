<?php
class PDOReportType extends ReportTypeBase {
	public static function init(&$report) {
		$environments = PhpReports::$config['environments'];
		
		if(!isset($environments[$report->options['Environment']][$report->options['Database']])) {
			throw new Exception("No ".$report->options['Database']." info defined for environment '".$report->options['Environment']."'");
		}

		//make sure the syntax highlighting is using the proper class
		SqlFormatter::$pre_attributes = "class='prettyprint linenums lang-sql'";
		
		$mysql = $environments[$report->options['Environment']][$report->options['Database']];
		
		//default host macro to mysql's host if it isn't defined elsewhere
		if(!isset($report->macros['host'])) $report->macros['host'] = $mysql['host'];
		
		//replace legacy shorthand macro format
		foreach($report->macros as $key=>$value) {
			$params = $report->options['Variables'][$key];
			
			//macros shortcuts for arrays
			if(isset($params['multiple']) && $params['multiple']) {
				//allow {macro} instead of {% for item in macro %}{% if not item.first %},{% endif %}{{ item.value }}{% endfor %}
				//this is shorthand for comma separated list
				$report->raw_query = preg_replace('/([^\{])\{'.$key.'\}([^\}])/','$1{% for item in '.$key.' %}{% if not loop.first %},{% endif %}\'{{ item }}\'{% endfor %}$2',$report->raw_query);
			
				//allow {(macro)} instead of {% for item in macro %}{% if not item.first %},{% endif %}{{ item.value }}{% endfor %}
				//this is shorthand for quoted, comma separated list
				$report->raw_query = preg_replace('/([^\{])\{\('.$key.'\)\}([^\}])/','$1{% for item in '.$key.' %}{% if not loop.first %},{% endif %}(\'{{ item }}\'){% endfor %}$2',$report->raw_query);
			}
			//macros sortcuts for non-arrays
			else {
				//allow {macro} instead of {{macro}} for legacy support
				$report->raw_query = preg_replace('/([^\{])(\{'.$key.'+\})([^\}])/','$1{$2}$3',$report->raw_query);
			}
		}
		
		//if there are any included reports, add the report sql to the top
		if(isset($report->options['Includes'])) {
			$included_sql = '';
			foreach($report->options['Includes'] as &$included_report) {
				$included_sql .= trim($included_report->raw_query)."\n";
			}
			
			$report->raw_query = $included_sql . $report->raw_query;
		}
		
		//set a formatted query here for debugging.  It will be overwritten below after macros are substituted.
		$report->options['Query_Formatted'] = SqlFormatter::format($report->raw_query);
	}
	
	public static function openConnection(&$report) {
		if(isset($report->conn)) return;
		
		$environments = PhpReports::$config['environments'];
		$config = $environments[$report->options['Environment']][$report->options['Database']];
		
		//the default is to use a user with read only privileges
		$type = $config['type'];
		$username = $config['user'];
		$password = $config['pass'];
		$host = $config['host'];
		$database = $config['database'];
		
		//if the report requires read/write privileges
		if(isset($report->options['access']) && $report->options['access']==='rw') {
			if(isset($config['user_rw'])) $username = $config['user_rw'];
			if(isset($config['pass_rw'])) $password = $config['pass_rw'];
			if(isset($config['host_rw'])) $host = $config['host_rw'];
		}
	
		$connectstring = "$type:host=$host";
		
		if(isset($config['database'])) {
            $connectstring .= ";dbname=$database";
		}

		if(!($report->conn = new PDO($connectstring, $username, $password))) {
			throw new Exception('Could not connect to database');
		}
	}
	
	public static function closeConnection(&$report) {
		if(!isset($report->conn)) return;
		$report->conn = null;
		unset($report->conn);
	}
	
	public static function getVariableOptions($params, &$report) {
		$query = 'SELECT DISTINCT '.$params['column'].' FROM '.$params['table'];
		
		if(isset($params['where'])) {
			$query .= ' WHERE '.$params['where'];
		}

		if(isset($params['order']) && in_array($params['order'], array('ASC', 'DESC')) ) {
			$query .= ' ORDER BY '.$params['column'].' '.$params['order'];
		}
		
		$result = $report->conn->query($query);
		
		if(!$result) {
			throw new Exception("Unable to get variable options");
		}
		
		$options = array();
		
		if(isset($params['all'])) $options[] = 'ALL';
		
		foreach ($result as $row) {
			$options[] = $row[$params['column']];
		}
		
		return $options;
	}
	
	public static function run(&$report) {		
		$macros = $report->macros;
		foreach($macros as $key=>$value) {
			if(is_array($value)) {
				$first = true;
				foreach($value as $key2=>$value2) {
					$value[$key2] = substr($report->conn->quote(trim($value2)), 1, -1);
					$first = false;
				}
				$macros[$key] = $value;
			}
			else {
				$macros[$key] = substr($report->conn->quote($value), 1, -1);
			}
			
			if($value === 'ALL') $macros[$key.'_all'] = true;
		}
		
		//add the config and environment settings as macros
		$macros['config'] = PhpReports::$config;
		$macros['environment'] = PhpReports::$config['environments'][$report->options['Environment']];
		
		//expand macros in query
		$sql = PhpReports::render($report->raw_query,$macros);
		
		$report->options['Query'] = $sql;

		$report->options['Query_Formatted'] = SqlFormatter::format($sql);
		
		//split into individual queries and run each one, saving the last result		
		$queries = SqlFormatter::splitQuery($sql);
		
		$datasets = array();
		
		$explicit_datasets = preg_match('/--\s+@dataset(\s*=\s*|\s+)true/',$sql);
		
		foreach($queries as $i=>$query) {
			$is_last = $i === count($queries)-1;
			
			//skip empty queries
			$query = trim($query);
			if(!$query) continue;
			
			$stmt = $report->conn->query($query, PDO::FETCH_ASSOC);
			if(!$stmt) {
				throw new Exception("Prepare Query failed");
			}

			//if this query had an assert=empty flag and returned results, throw error
			if(preg_match('/^--[\s+]assert[\s]*=[\s]*empty[\s]*\n/',$query)) {
				if($stmt)  throw new Exception("Assert failed.  Query did not return empty results.");
			}
			
			// If this query should be included as a dataset
			if((!$explicit_datasets && $is_last) || preg_match('/--\s+@dataset(\s*=\s*|\s+)true/',$query)) {
				$dataset = array('rows'=>array());
				
				foreach ($stmt as $row) {
					$dataset['rows'][] = $row;
				}
				
				// Get dataset title if it has one
				if(preg_match('/--\s+@title(\s*=\s*|\s+)(.*)/',$query,$matches)) {
					$dataset['title'] = $matches[2];
				}
				
				$datasets[] = $dataset;
			}
		}
		
		return $datasets;
	}
}
