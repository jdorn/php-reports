<?php
class AdoReportType extends ReportTypeBase {
	public static function init(&$report) {
		$environments = PhpReports::$config['environments'];
		
		if(!isset($environments[$report->options['Environment']][$report->options['Database']])) {
			throw new Exception("No ".$report->options['Database']." database defined for environment '".$report->options['Environment']."'");
		}

		//make sure the syntax highlighting is using the proper class
		SqlFormatter::$pre_attributes = "class='prettyprint linenums lang-sql'";
		
		//default host macro to mysql's host if it isn't defined elsewhere
		//if(!isset($report->macros['host'])) $report->macros['host'] = $mysql['host'];
		
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
		
		if(!($report->conn = ADONewConnection($config['uri']))) {
			throw new Exception('Could not connect to the database: '.$report->conn->ErrorMsg());
		}
	}
	
	public static function closeConnection(&$report) {
		if (!isset($report->conn)) return;
		if ($report->conn->IsConnected()) {
			$report->conn->Close();
		}
		unset($report->conn);
	}
	
	public static function getVariableOptions($params, &$report) {
        $report->conn->SetFetchMode(ADODB_FETCH_NUM);
        $query = 'SELECT DISTINCT '.$params['column'].' FROM '.$params['table'];
		
		if(isset($params['where'])) {
			$query .= ' WHERE '.$params['where'];
		}
		
		$result = $report->conn->Execute($query);
		
		if (!$result) {
			throw new Exception("Unable to get variable options: ".$report->conn->ErrorMsg());
		}

		$options = array();
		
		if(isset($params['all']) && $params['all']) {
            $options[] = 'ALL';
        }

        while ($row = $result->FetchRow()) {
            if ($result->FieldCount() > 1) {
                $options[] = array('display'=>$row[0], 'value'=>$row[1]);
            } else {
                $options[] = $row[0];
            }
        }

        return $options;
	}
	
	public static function run(&$report) {
        $report->conn->SetFetchMode(ADODB_FETCH_ASSOC);
        $rows = array();
		
		$macros = $report->macros;
		foreach($macros as $key=>$value) {
			if(is_array($value)) {
				$first = true;
				foreach($value as $key2=>$value2) {
					$value[$key2] = mysql_real_escape_string(trim($value2));
					$first = false;
				}
				$macros[$key] = $value;
			}
			else {
				$macros[$key] = mysql_real_escape_string($value);
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
		
		foreach($queries as $query) {
			//skip empty queries
			$query = trim($query);
			if(!$query) continue;
			
			$result = $report->conn->Execute($query);
			if(!$result) {
				throw new Exception("Query failed: ".$report->conn->ErrorMsg());
			}
			
			//if this query had an assert=empty flag and returned results, throw error
			if(preg_match('/^--[\s+]assert[\s]*=[\s]*empty[\s]*\n/',$query)) {
				if($result->GetAssoc()) {
					throw new Exception("Assert failed.  Query did not return empty results.");
				}
			}
		}
		
		return $result->GetArray();
	}
}
