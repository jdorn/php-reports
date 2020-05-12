<?php
class MongoReportType extends ReportTypeBase {
	public static function init(&$report) {
		$environments = PhpReports::$config['environments'];
		
		if(!isset($environments[$report->options['Environment']][$report->options['Database']])) {
			throw new Exception("No ".$report->options['Database']." database defined for environment '".$report->options['Environment']."'");
		}
		
		$mongo = $environments[$report->options['Environment']][$report->options['Database']];
		
		//default host macro to mysql's host if it isn't defined elsewhere
		if(!isset($report->macros['host']) && isset($mongo['host'])) $report->macros['host'] = $mongo['host'];
		
		//if there are any included reports, add it to the top of the raw query
		if(isset($report->options['Includes'])) {
			$included_code = '';
			foreach($report->options['Includes'] as &$included_report) {
				$included_code .= trim($included_report->raw_query)."\n";
			}
			
			$report->raw_query = $included_code . $report->raw_query;
		}
	}
	
	public static function openConnection(&$report) {
		
	}
	
	public static function closeConnection(&$report) {
		
	}
	
	public static function run(&$report) {		
		$eval = '';
		foreach($report->macros as $key=>$value) {
			if(is_array($value)) {
				$value = json_encode($value);
			}
			else {
				$value = '"'.addslashes($value).'"';
			}
			
			$eval .= 'var '.$key.' = '.$value.';'."\n";
		}
		$eval .= $report->raw_query;

		$environments = PhpReports::$config['environments'];
		$config = $environments[$report->options['Environment']][$report->options['Database']];
		
		$mongo_database = isset($report->options['Mongodatabase'])? $report->options['Mongodatabase'] : '';

		// Get connection string
        $connectionString = '';
        if(isset($config['dsn'])) {
            // Replace any existing mongo database name with the one for the report
            $connectionString = preg_replace(
                '/(mongodb[a-zA-Z+]*:\/\/)?([^\/?]+)(\/[^\.,:?]*)?($|\?)/',
                '$1$2/'.$mongo_database.'$4',
                $config['dsn'],
                1
            );
        }
        elseif(isset($config['host'])) {
            $connectionString = $config['host'].':'.(isset($config['port'])?$config['port']:27017).'/'.$mongo_database;
        }

        //command without eval string
		$command = 'mongo '.escapeshellarg($connectionString).' --quiet --eval ';

        // Remove password from command for debug info
        $commandSanitized = preg_replace('/\/\/([^@:]+)(:[^:@]+)@/','//$1:*******@',$command);

		//easy to read formatted query
		$report->options['Query_Formatted'] = '<div>
			<pre style="background-color: black; color: white; padding: 10px 5px;">$ '.$commandSanitized.'"..."</pre>'.
			'Eval String:'.
			'<pre class="prettyprint linenums lang-js">'.htmlentities($eval).'</pre>
		</div>';

		//escape the eval string and add it to the command
		$command .= escapeshellarg($eval);
		$report->options['Query'] = '$ '.$commandSanitized.escapeshellarg($eval);

		//include stderr so we can capture shell errors (like "command mongo not found")
		$result = shell_exec($command.' 2>&1');
		
		$result = trim($result);
		
		$json = json_decode($result, true);
		if($json === NULL) throw new Exception($result);
		
		return $json;
	}
}
