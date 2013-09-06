<?php
class AdoPivotReportType extends ReportTypeBase {
	public static function init(&$report) {
		$environments = PhpReports::$config['environments'];
		
		if(!isset($environments[$report->options['Environment']][$report->options['Database']])) {
			throw new Exception("No ".$report->options['Database']." database defined for environment '".$report->options['Environment']."'");
		}

		//make sure the syntax highlighting is using the proper class
		SqlFormatter::$pre_attributes = "class='prettyprint linenums lang-sql'";

        $object = spyc_load($report->raw_query);

        $report->raw_query = array();
		//if there are any included reports, add the report sql to the top
		if(isset($report->options['Includes'])) {
			$included_sql = '';
			foreach($report->options['Includes'] as &$included_report) {
				$included_sql .= trim($included_report->raw_query)."\n";
			}
            if (strlen($included_sql) > 0) {
			    $report->raw_query[] = $included_sql;
            }
		}

        $report->raw_query[] = $object;

		//set a formatted query here for debugging.  It will be overwritten below after macros are substituted.
        //We can not set the query here - it's not a query just yet...
		//$report->options['Query_Formatted'] = SqlFormatter::format($report->raw_query);
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

        $macros = $report->macros;
        foreach($macros as $key=>$value) {
            if(is_array($value)) {
                foreach($value as $key2=>$value2) {
                    $value[$key2] = mysql_real_escape_string(trim($value2));
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

		$result = $report->conn->Execute(PhpReports::renderString($query, $macros));
		
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

        $raw_sql = "";
        foreach ($report->raw_query as $qry) {
            if (is_array($qry)) {
                foreach ($qry as $key=>$value) {
                    // TODO handle arrays better
                    if (!is_bool($value) && !is_array($value)) {
                        $qry[$key] = PhpReports::renderString($value, $macros);
                    }
                }
                //TODO This sux - need a class or something :-)
                $raw_sql .= PivotTableSQL($report->conn, $qry['tables'], $qry['rows'], $qry['columns'], $qry['where'], $qry['orderBy'], $qry['limit'], $qry['agg_field'], $qry['agg_label'], $qry['agg_fun'], $qry['include_agg_field'], $qry['show_count']);
            } else {
                $raw_sql .= $qry;
            }
        }

        //expand macros in query
        $sql = PhpReports::render($raw_sql, $macros);

        $report->options['Query'] = $sql;

        $report->options['Query_Formatted'] = SqlFormatter::format($sql);

        //split into individual queries and run each one, saving the last result
        $queries = SqlFormatter::splitQuery($sql);

        foreach($queries as $query) {
            if (!is_array($query)) {
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
        }

        return $result->GetArray();
	}
}
